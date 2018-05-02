<?php
/**
 * Interface DataStoreInterface | ZedBoot/System/DataStore/DataStoreInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DataStore
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Data store
 * Provides a very simple interface for storing and retrieving information.
 */

namespace ZedBoot\System\DataStore;
interface DataStoreInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	function lockAndRead(&$data);
	function writeAndUnlock($data);
	function lock();
	/**
	 * Should return error if data store has not been locked
	 * @param mixed $data result will be stored here
	 * @return boolean error status
	 */
	function read(&$data);
	/**
	 * @param mixed $data
	 */
	function write($data);
	function unlock();
	/**
	 * lock, read, and unlock
	 * @param mixed $data result will be stored here
	 * @return boolean error status
	 */
	function quickRead(&$data);
	/**
	 * lock, write, and unlock
	 */
	function quickWrite($data);
}
