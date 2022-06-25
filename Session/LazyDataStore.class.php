<?php
/**
 * Interface DataStoreInterface | ZedBoot/DataStore/DataStoreInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     DataStore
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 20122 Jonathan Hulka
 */

/**
 * Implementation of \ZedBoot\DataStore\DataStoreInterface that loads only when needed.
 * If DataStore functions are not used, the session will not be affected.
 * BE CAREFUL - if expiry is in effect, this class will not refresh timeouts unless DataStore functions are actually used.
 */
namespace ZedBoot\Session;
interface LazyDataStore implements \ZdeBoot\DataStore\DataStoreInterface
{
	protected
		$session,
		$key,
		$expiry,
		$forceCreate,
		$dataStore = null;

	public function __construct
	(
		\ZedBoot\Session\SessionInterface $session,
		string $key,
		int $expiry = null,
		bool $forceCreate = true
	)
	{
		$this->session = $session;
		$this->key = $key;
		$this->expiry = $expiry;
		$this->forceCreate = $forceCreate;
	}

	public function lockAndRead()
	{
		return $this->getDataStore()->lockAndRead();
	}

	public function writeAndUnlock($data)
	{
		$this->getDataStore()->writeAndUnlock($data);
	}

	public function lock()
	{
		$this->getDataStore()->lock();
	}

	public function read()
	{
		return $this->getDataStore->read();
	}

	public function write($data)
	{
		$this->getDataStore->write($data);
	}

	public function unlock()
	{
		$this->getDataStore->unlock();
	}

	public function quickRead()
	{
		return $this->getDataStore->quickRead();
	}

	public function quickWrite($data)
	{
		$this->getDataStore->quickWrite($data);
	}

	protected function getDataStore()
	{
		if($this->dataStore === null) $this->dataStore = $this->session->getDataStore($this->key, $this->expiry, $this->forceCreate);
		return $this->dataStore;
	}
}
