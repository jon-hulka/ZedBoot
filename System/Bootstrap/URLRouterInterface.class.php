<?php
/**
 * Interface URLRouterInterface | ZedBoot/System/Bootstrap/URLRouterInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * URL router interface
 * Defines the interface for mapping urls
 */
namespace ZedBoot\System\Bootstrap;
interface URLRouterInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	public function parseURL($url);
//	public function getRouteParameter($name);
//	public function getAllRouteParameters();
	public function getBaseURL();
	public function getURLParameters();
	public function getURLParts();
	public function getRouteData();
}
