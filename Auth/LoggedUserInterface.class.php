<?php
/**
 * Interface LoggedUserInterface | ZedBoot/Auth/LoggedUserInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017 - 2020 Jonathan Hulka
 */

/**
 * Persistent user state
 * LoggedUserInterface defines a model for keeping track of the currently logged in user
 */
namespace ZedBoot\Auth;
interface LoggedUserInterface
{
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed null if no user logged in, user data if a user is logged in
	 */
	public function getUser();
	/**
	 * Sets user for the session
	 * @param String $id
	 */
	public function setUser(
		string $id);
	/**
	 * Clears user for the session
	 */
	public function clearUser();
}
