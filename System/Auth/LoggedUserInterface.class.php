<?php
/**
 * Interface LoggedUserInterface | ZedBoot/System/Bootstrap/LoggedUserInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017-2018 Jonathan Hulka
 */

/**
 * Persistent user state
 * LoggedUserInterface defines a model for keeping track of the currently logged in user
 */
namespace ZedBoot\System\Auth;
interface LoggedUserInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed array of user properties if a user is logged in, null if no user logged in, false on error
	 */
	public function getUser();
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed user id if logged in, null if not logged in, false on error
	 */
	public function getId();
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed user name if logged in, null if not logged in, false on error
	 */
	public function getName();
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed user info if logged in, null if not logged in or no info available, false on error
	 */
	public function getInfo();
	/**
	 * Should implicitly load data if not loaded
	 * @return mixed array of roles if logged in, null if not logged in, false on error
	 */
	public function getRoles();
	/**
	 * Sets user data for the session
	 * @param $id String user id
	 * @param $name String user name
	 * @param $info Array user information such as first and last name, etc
	 * @param $roles Array user permissions
	 */
	public function setUser($id,$name,Array $info,Array $roles);
	/**
	 * Clears user data for the session
	 */
	public function clearUser();
}
