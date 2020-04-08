<?php
/**
 * Interface URLRouterInterface | ZedBoot/Bootstrap/URLRouterInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * URL router interface
 * Defines the interface for mapping urls
 */
namespace ZedBoot\Bootstrap;
interface URLRouterInterface
{
	public function parseURL($url);
	/**
	 * @return string the url substring that selected the route (if the route 'foo/bar' was selected for the url 'foo/bar/baz', 'foo/bar' would be returned)
	 */
	public function getBaseURL(): ?string;
	/**
	 * @return array url segments not used in selecting the route (if the route 'foo' was selected for the url 'foo/bar/baz', ['bar','baz'] would be returned)
	 */
	public function getURLParameters(): ?array;
	/**
	 * @return array same as getBaseURL, except split into segments (if getBaseURL returns 'foo/bar', getURLParts returns ['foo','bar'])
	 */
	public function getURLParts(): ?array;
	/**
	 * @return array data specific to the route (typically this will refer to a ResponseInterface implementation to handle the request)
	 */
	public function getRouteData(): ?array;
}
