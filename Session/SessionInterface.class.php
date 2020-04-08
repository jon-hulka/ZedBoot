<?php
/**
 * Interface SessionInterface | ZedBoot/Session/SessionInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Interface for fine-grained session management
 * Implementations are factories that produce implementations of \ZedBoot\DataStoreInterface
 * Garbage collection should be handled transparently.
 */
namespace ZedBoot\Session;
interface SessionInterface
{
	/**
	 * If the DataStore has not been accessed in the expiry period, it should be cleared
	 * @param $key String alphanumeric segments delimited by forward slash
	 * @param $expiry mixed optional expiry in seconds, if null default will be used, if 0 no expiry
	 * @param boolean $forceCreate if true, nonexistent or expired datastore will be created
	 * @return mixed \ZedBoot\DataStore\DataStoreInterface on success, null if $forceCreate is false and datastore was expired or nonexistent
	 */
	public function getDataStore(string $key,int $expiry=null, bool $forceCreate=true): ? \ZedBoot\DataStore\DataStoreInterface;
	/**
	 * Removes all data in the session
	 * @param String $keyRoot If not empty, only clear DataStores within the specified space ('foo/bar' will cause 'foo/bar and 'foo/bar/baz' to be cleared, but not 'foo')
	 */
	public function clearAll(string $keyRoot='');
}
