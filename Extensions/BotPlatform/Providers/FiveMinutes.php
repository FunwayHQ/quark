<?php
namespace Quark\Extensions\BotPlatform\Providers;

use Quark\QuarkDTO;
use Quark\QuarkHTTPClient;
use Quark\QuarkJSONIOProcessor;

use Quark\Extensions\BotPlatform\IQuarkBotPlatformProvider;
use Quark\Extensions\BotPlatform\IQuarkBotPlatformEvent;

use Quark\Extensions\BotPlatform\BotPlatformActor;
use Quark\Extensions\BotPlatform\Events\BotPlatformEventMessage;
use Quark\Extensions\BotPlatform\Events\BotPlatformEventTyping;

/**
 * Class FiveMinutes
 *
 * @package Quark\Extensions\BotPlatform\Providers
 */
class FiveMinutes implements IQuarkBotPlatformProvider {
	const PLATFORM = 'FiveMinutes';
	
	//const API_ENDPOINT = 'http://5min.im/';
	const API_ENDPOINT = 'http://fm.alex025.dev.funwayhq.com/';

	const EVENT_MESSAGE = 'e.message';
	const EVENT_TYPING = 'e.typing';
	const EVENT_ONLINE = 'e.online';
	const EVENT_OFFLINE = 'e.offline';
	const EVENT_ROOM_READY = 'e.room.ready';
	const EVENT_ROOM_START = 'e.room.start';
	const EVENT_ROOM_PAUSE = 'e.room.pause';
	const EVENT_ROOM_INVITE = 'e.room.invite';
	const EVENT_ROOM_SELECT = 'e.room.select';

	const MESSAGE_TEXT = 'text';
	const MESSAGE_IMAGE = 'image';
	const MESSAGE_STICKER = 'sticker';

	/**
	 * @var string $_appId = ''
	 */
	private $_appId = '';

	/**
	 * @var string $_appSecret = ''
	 */
	private $_appSecret = '';

	/**
	 * @param string $appId
	 * @param string $appSecret
	 *
	 * @return mixed
	 */
	public function BotApplication ($appId, $appSecret) {
		$this->_appId = $appId;
		$this->_appSecret = $appSecret;
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return bool
	 */
	public function BotValidation (QuarkDTO $request) {
		return $request->signature == sha1($this->_appSecret);
	}

	/**
	 * @param QuarkDTO $request
	 *
	 * @return IQuarkBotPlatformEvent
	 */
	public function BotIn (QuarkDTO $request) {
		if ($request->event == self::EVENT_MESSAGE) {
			$event = BotPlatformEventMessage::BotEventIn(
				new BotPlatformActor($request->actor->_id, $request->actor->name),
				$request->room,
				self::PLATFORM
			);

			$event->Payload($request->data->payload);
			$event->ID($request->data->_id);
			$event->Type(self::TypeIn($request->data->type));

			return $event;
		}

		if ($request->event == self::EVENT_TYPING) {
			$event = BotPlatformEventTyping::BotEventIn(
				new BotPlatformActor($request->actor->_id, $request->actor->name),
				$request->room,
				self::PLATFORM
			);

			$event->Duration(0);
			$event->Sync(true);

			return $event;
		}

		return null;
	}

	/**
	 * @param IQuarkBotPlatformEvent $event
	 *
	 * @return bool
	 */
	public function BotOut (IQuarkBotPlatformEvent $event) {
		if ($event instanceof BotPlatformEventMessage) {
			$api = $this->BotAPI('chat/message', array(
				'bot' => $this->_appSecret,
				'room' => $event->BotEventChannel(),
				'type' => self::TypeOut($event->Type()),
				'payload' => $event->Payload()
			));

			return isset($api->status) && $api->status == 200;
		}

		if ($event instanceof BotPlatformEventTyping) {
			$api = $this->BotAPI('chat/room/typing', array(
				'bot' => $this->_appSecret,
				'room' => $event->BotEventChannel(),
				'duration' => $event->Duration()
			), $event->Sync());

			return isset($api->status) && $api->status == 200;
		}

		return false;
	}

	/**
	 * @param string $method
	 * @param array $data
	 * @param bool $sync = true
	 *
	 * @return QuarkDTO
	 */
	public function BotAPI ($method, $data, $sync = true) {
		$request = QuarkDTO::ForPOST(new QuarkJSONIOProcessor());
		$request->Data($data);

		$response = new QuarkDTO(new QuarkJSONIOProcessor());

		return QuarkHTTPClient::To(self::API_ENDPOINT . $method, $request, $response, null, 10, $sync);
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public static function TypeIn ($type) {
		if ($type == FiveMinutes::MESSAGE_TEXT)
			return BotPlatformEventMessage::TYPE_TEXT;

		if ($type == FiveMinutes::MESSAGE_IMAGE)
			return BotPlatformEventMessage::TYPE_IMAGE;

		if ($type == FiveMinutes::MESSAGE_STICKER)
			return BotPlatformEventMessage::TYPE_STICKER;

		return $type;
	}

	/**
	 * @param string $type
	 *
	 * @return string
	 */
	public static function TypeOut ($type) {
		if ($type == BotPlatformEventMessage::TYPE_TEXT)
			return FiveMinutes::MESSAGE_TEXT;

		if ($type == BotPlatformEventMessage::TYPE_IMAGE)
			return FiveMinutes::MESSAGE_IMAGE;

		if ($type == BotPlatformEventMessage::TYPE_STICKER)
			return FiveMinutes::MESSAGE_STICKER;

		return $type;
	}
}