<?php
namespace Quark\ViewResources\Quark;

use Quark\IQuarkViewResource;
use Quark\IQuarkViewResourceWithDependencies;
use Quark\IQuarkLocalViewResource;
use Quark\IQuarkViewResourceType;

use Quark\QuarkJSViewResourceType;
use Quark\QuarkLocalCoreJSViewResource;
use Quark\ViewResources\jQuery;

/**
 * Class UX
 *
 * @package Quark\ViewResources\Quark
 */
class UX implements IQuarkViewResource, IQuarkLocalViewResource, IQuarkViewResourceWithDependencies {
	/**
	 * @return IQuarkViewResourceType
	 */
	public function Type () {
		return new QuarkJSViewResourceType();
	}

	/**
	 * @return string
	 */
	public function Location () {
		return __DIR__ . '/JS/Quark.UX.js';
	}

	/**
	 * @return bool
	 */
	public function CacheControl () {
		return true;
	}

	/**
	 * @return array
	 */
	public function Dependencies () {
		return array(
			new QuarkLocalCoreJSViewResource(),
			new jQuery\Core(),
			new IO()
		);
	}
}