<?php
/**
 * Interface RequestHandlerInterface | ZedBoot/System/Bootstrap/RequestHandlerInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Bootstrap
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Request Handler interface
 * Defines the interface for handling requests and writing responses.
 * Assuming no errors, the init script:
 *  - buffers output
 *  - calls setDependencyLoader()
 *  - calls handleRequest()
 *  - discards output buffer
 *  - calls writeResponse()
 */
namespace ZedBoot\System\Bootstrap;
interface RequestHandlerInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * @return void
	 */
	public function setDependencyLoader(\ZedBoot\System\DI\DependencyLoaderInterface $dependencyLoader);
	/**
	 * Performs requested actions
	 * Typically, the controller will be invoked here.
	 * Caller (init script) buffers and discards output.
	 * Errors will be logged and treaded as '500 Internal Server Error'; other status codes should be written by writeResponse().
	 * @return boolean true on success, false on error. In case of error, getError() should return the relevant details.
	 */
	public function handleRequest();
	/**
	 * Writes output
	 * Typically, the view will be invoked here.
	 * @return void No return value, just output.
	 */
	public function writeResponse();
}
