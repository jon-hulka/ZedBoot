<?php
/**
 * Interface LoggedUserInterface | ZedBoot/Auth/LoggedUserInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017-2018 Jonathan Hulka
 */

/**
 * Persistent user state
 * LoggedUserInterface defines a model for keeping track of the currently logged in user
 */
namespace ZedBoot\Auth;
interface LoggedUserInterface
{
	/**
	 * In case of failure, returns user-friendly message.
	 * @return mixed user friendly message from last failure, if any.
	 */
	public function getMessage();
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed null if no user logged in, false on failure, otherwise Array('id'=><user id>,'username'=><user name>,'info'=><info>,'roles'=><roles>,'modTime'=><mod time>,'loginTime'=><login time>)
	 * modTime and loginTime should be unix timestamps (expressed in seconds, decimals are OK)
	 * modTime indicates last time the user was modified (if there are no recent changes (since login time), a value of 0 is acceptable)
	 */
	public function getUser();
	/**
	 * Sets user for the session
	 * @param String $id
	 * @param String $username
	 * @param Array $info miscellaneous user data
	 * @param Array $roles roles assigned to the user
	 * @param Numeric $modTime last time the user was modified
	 * @return boolean true on success, false on failure
	 */
	public function setUser(
		$id,
		$username,
		Array $info,
		Array $roles,
		$modTime);
	/**
	 * Clears user for the session
	 */
	public function clearUser();
}
