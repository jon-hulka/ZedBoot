<?php
/**
 * Interface ResponseInterface | ZedBoot/Bootstrap/ResponseInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Request Handler interface
 * Every page must have an implementation of ResponseInterface.
 * The init script calls handleRequest, getResponseText, and getHeaders in that order
 */
namespace ZedBoot\Bootstrap;
interface ResponseInterface
{
	/**
	 * Performs requested actions
	 * Typically the controller will be invoked here.
	 */
	public function handleRequest();
	
	/**
	 * @return Array 0 or more arrays of header parameters array(array(<header>,<optional replace>,<optional response code>), ...)
	 */
	public function getHeaders(): array;

	/**
	 * Typically the view will be invoked here.
	 * @return String page response
	 */
	public function getResponseText(): string;
}
