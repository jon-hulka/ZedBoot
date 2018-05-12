<?php
/**
 * Interface SessionInterface | ZedBoot/System/Session/SessionInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Interface for fine-grained session management
 * Implementations are factories that produce implementations of \ZedBoot\System\DataStoreInterfase
 */
namespace ZedBoot\System\Session;
interface SessionInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * If the DataStore has not been accessed in the expiry period, it should be cleared
	 * @param $key String alphanumeric segments delimited by forward slash
	 * @param $expiry mixed optional expiry in seconds, if null default will be used, if 0 no expiry
	 * @param boolean $forceCreate if true, nonexistent or expired datastore will be created
	 * @return mixed \ZedBoot\System\DataStore\DataStoreInterface on success, null if $forceCreate is false and datastore was expired or nonexistent, false on error
	 */
	public function getDataStore($key,$expiry=null,$forceCreate=true);
	/**
	 * Run garbage collection
	 * This might be done automatically - check implementation for specifics
	 * @param mixed $lifetime seconds: should match or exceed the expiry of any session instance, null for default
	 */
	public function gc($lifetime=null);
}
