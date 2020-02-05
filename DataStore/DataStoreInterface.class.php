<?php
/**
 * Interface DataStoreInterface | ZedBoot/DataStore/DataStoreInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     DataStore
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Data store
 * Provides a very simple interface for storing and retrieving information.
 */

namespace ZedBoot\DataStore;
interface DataStoreInterface
{
	public function lockAndRead();
	public function writeAndUnlock($data);
	public function lock();
	/**
	 * Should throw an exception if data store has not been locked
	 * @return mixed data, null if no data has been written
	 */
	public function read();
	/**
	 * Should throw an exception if data store has not been locked
	 * @param mixed $data
	 */
	public function write($data);
	public function unlock();
	/**
	 * lock, read, and unlock
	 * @return mixed data
	 */
	public function quickRead();
	/**
	 * lock, write, and unlock
	 */
	public function quickWrite($data);
}
