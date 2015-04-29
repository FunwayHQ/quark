<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkViewResource;
use Quark\IQuarkInlineViewResource;

/**
 * Class QuarkResponsiveUI
 *
 * @package Quark\ViewResources\Quark
 */
class QuarkResponsiveUI implements IQuarkViewResource, IQuarkInlineViewResource {
	const DEVICE_WIDTH = 'device-width';
	const DEVICE_HEIGHT = 'device-height';
	const SCALE1 = 1.0;
	const SCALE2 = 2.0;

	private $_width = self::DEVICE_WIDTH;
	private $_scale = self::SCALE1;

	/**
	 * @param string $width
	 * @param float $scale
	 */
	public function __construct ($width = self::DEVICE_HEIGHT, $scale = self::SCALE1) {
		$this->_width = $width;
		$this->_scale = $scale;
	}

	/**
	 * @return string
	 */
	public function Location () {
		// TODO: Implement Location() method.
	}

	/**
	 * @return string
	 */
	public function Type () {
		// TODO: Implement Type() method.
	}

	/**
	 * @return string
	 */
	public function HTML () {
		return '<meta name="viewport" content="width=' . $this->_width . ', initial-scale=' . $this->_scale . '" />';
	}
}