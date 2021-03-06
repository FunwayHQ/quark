<?php
namespace Quark\Extensions\AceStream;

use Quark\IQuarkExtension;

use Quark\Quark;
use Quark\QuarkCollection;
use Quark\QuarkDTO;
use Quark\QuarkFormIOProcessor;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;
use Quark\QuarkModel;
use Quark\QuarkURI;

/**
 * Class AceStream
 *
 * @package Quark\Extensions\AceStream
 */
class AceStream implements IQuarkExtension {
	const LOCAL_PORT = 6878;

	const CLOUD_URL = 'https://search.acestream.net';
	const CLOUD_TEST_KEY = 'test_api_key';
	const CLOUD_VERSION = '1.0';

	const CATEGORY_INFORMATIONAL = 'informational';
	const CATEGORY_ENTERTAINING = 'entertaining';
	const CATEGORY_EDUCATIONAL = 'educational';
	const CATEGORY_MOVIES = 'movies';
	const CATEGORY_DOCUMENTARIES = 'documentaries';
	const CATEGORY_SPORT = 'sport';
	const CATEGORY_FASHION = 'fashion';
	const CATEGORY_MUSIC = 'music';
	const CATEGORY_REGIONAL = 'regional';
	const CATEGORY_ETHNIC = 'ethnic';
	const CATEGORY_RELIGION = 'religion';
	const CATEGORY_TELESHOP = 'teleshop';
	const CATEGORY_EROTIC_18_PLUS = 'erotic_18_plus';
	const CATEGORY_OTHER_18_PLUS = 'other_18_plus';
	const CATEGORY_CYBER_GAMES = 'cyber_games';
	const CATEGORY_AMATEUR = 'amateur';
	const CATEGORY_WEBCAM = 'webcam';
	const CATEGORY_KIDS = 'kids';
	const CATEGORY_SERIES = 'series';

	const PAGE_SIZE_DEFAULT = 10;
	const PAGE_SIZE_MAX = 50;

	/**
	 * @var AceStreamConfig $_config
	 */
	private $_config;

	/**
	 * @var string $_localToken = ''
	 */
	private $_localToken = '';

	/**
	 * @param string $name = ''
	 */
	public function __construct ($name = '') {
		$this->_config = Quark::Config()->Extension($name);
	}

	/**
	 * @param string $hash = ''
	 * @param bool $json = false
	 *
	 * @return string
	 */
	private function _stream ($hash = '', $json = false) {
		return $this->_config->LocalURI() . '/ace/getstream?infohash=' . $hash . ($json ? '&format=json' : '');
	}

	/**
	 * @param string $hash = ''
	 *
	 * @return string
	 */
	public function StreamHTTPProgressive ($hash = '') {
		return $this->_stream($hash);
	}

	/**
	 * @param string $hash = ''
	 *
	 * @return AceStreamStream
	 */
	public function Stream ($hash = '') {
		$response = $this->API($this->_stream($hash, true));
		if (!isset($response->response)) return null;

		$out = new AceStreamStream();

		if (isset($response->response->playback_session_id))
			$out->Session($response->response->playback_session_id);

		$out->Live($response->response->is_live);

		$out->URIPlayback(QuarkURI::FromURI($this->_config->LocalURI() . QuarkURI::FromURI($response->response->playback_url)->Query()));
		$out->URICommand(QuarkURI::FromURI($this->_config->LocalURI() . QuarkURI::FromURI($response->response->command_url)->Query()));
		$out->URIStat(QuarkURI::FromURI($this->_config->LocalURI() . QuarkURI::FromURI($response->response->stat_url)->Query()));

		return $out;
	}

	/**
	 * @param string $url = ''
	 * @param array|object $data = []
	 *
	 * @return mixed
	 */
	public function API ($url = '', $data = []) {
		$request = QuarkDTO::ForGET(new QuarkFormIOProcessor());
		$request->URIParams($data);

		$response = QuarkHTTPClient::To($url, $request, new QuarkDTO(new QuarkJSONIOProcessor()));

		if (isset($response->error)) {
			Quark::Log('[AceStream::API] Error: ' . $response->error, Quark::LOG_WARN);

			return null;
		}

		return $response;
	}

	/**
	 * @param bool $regenerate = false
	 *
	 * @return string
	 */
	public function LocalToken ($regenerate = false) {
		if ($this->_localToken == '' || $regenerate) {
			$response = $this->API($this->_config->LocalURI() . '/server/api', array(
				'method' => 'get_api_access_token'
			));

			$this->_localToken = isset($response->result) && isset($response->result->token) ? $response->result->token : '';
		}

		return $this->_localToken;
	}

	/**
	 * @param array|object $params = []
	 *
	 * @return mixed
	 */
	public function SearchRaw ($params = []) {
		$init = array(
			'method' => 'search'
		);

		if ($this->_config->CloudMode()) {
			$url = self::CLOUD_URL . '/';

			$init['api_key'] = $this->_config->CloudKey();
			$init['api_version'] = $this->_config->CloudVersion();
		}
		else {
			$url = $this->_config->LocalURI() . '/server/api';

			$init['token'] = $this->LocalToken();
		}

		return $this->API($url, array_replace($init, $params));
	}

	/**
	 * @param string $query = ''
	 * @param int $page = 0
	 * @param int $count = self::PAGE_SIZE_DEFAULT
	 *
	 * @return QuarkCollection|AceStreamChannel[]
	 */
	public function Search ($query = '', $page = 0, $count = self::PAGE_SIZE_DEFAULT) {
		$response = $this->SearchRaw(array(
			'query' => $query,
			'page' => $page,
			'page_size' => $count
		));

		$out = new QuarkCollection(new AceStreamChannel());

		if (isset($response->total) && is_int($response->total))
			$out->Pages(ceil($response->total / $count));

		if (isset($response->results) && is_array($response->results))
			$out->PopulateModelsWith($response->results)
				->Change(array(), array('config' => $this->_config->ExtensionName()));

		return $out;
	}

	/**
	 * @param string $query = ''
	 * @param int $page = 0
	 * @param int $count = self::PAGE_SIZE_DEFAULT
	 *
	 * @return QuarkCollection|AceStreamChannelGroup[]
	 */
	public function SearchGroup ($query = '', $page = 0, $count = self::PAGE_SIZE_DEFAULT) {
		$response = $this->SearchRaw(array(
			'query' => $query,
			'page' => $page,
			'page_size' => $count,
			'group_by_channels' => 1,
			'show_epg' => 1
		));

		$out = new QuarkCollection(new AceStreamChannelGroup());

		if (isset($response->total) && is_int($response->total))
			$out->Pages(ceil($response->total / $count));

		if (isset($response->results) && is_array($response->results))
			$out->PopulateModelsWith($response->results)
				->Each(function ($item) {
					/**
					 * @var QuarkModel|AceStreamChannelGroup $item
					 */
					$item->items->Change(array(), array('config' => $this->_config->ExtensionName()));

					return $item;
				});

		return $out;
	}

	/**
	 * @return QuarkCollection|AceStreamChannel[]
	 */
	public function SearchSnapshot () {
		$response = $this->API(self::CLOUD_URL . '/all', array(
			'api_key' => $this->_config->CloudKey(),
			'api_version' => $this->_config->CloudVersion()
		));

		$out = new QuarkCollection(new AceStreamChannel());

		if (isset($response->result) && isset($response->result->results) && is_array($response->result->results))
			$out->PopulateModelsWith($response->result->results);

		return $out;
	}
}