<?php
/**
 * Interface AccountManagerInterface | ZedBoot/System/Auth/AccountManagerInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * User creation and management
 * AccountManagerInterface defines a model for managing user accounts
 */
namespace ZedBoot\System\Auth;
interface AccountManagerInterface
{
	/**
	 * In case of failure, returns user-friendly message.
	 * @return mixed user friendly message from last failure, if any.
	 */
	public function getMessage();
	/**
	 * @param $name String unique user name
	 * @param $info Array user information such as first and last name, etc
	 * @param $roles Array user permissions
	 * @return mixed false on failure, user id on success
	 */
	public function createUser($name,Array $info,Array $roles);
	/**
	 * Removes a user.
	 * @param string id of user to delete.
	 * @return boolean false on failure, true on success.
	 */
	public function deleteUser($id);
	/**
	 * To be used as a preparation for changePassword()
	 * @param $password string new password to be applied by changePassword().
	 */
	public function setNewPassword($password);
	/**
	 * To be used after setNewPassword. Applies the password to a user.
	 * @param $id string user id
	 * @return boolean false on failure, true on success.
	 */
	public function changePassword($id);
	/**
	 * Adds miscellaneous user information.
	 * @param $id string user id
	 * @param $info array user information
	 * @return boolean false on failure, true on success.
	 */
	public function setInfo($id,Array $info);
	/**
	 * Adds role privileges.
	 * @param $id string user id
	 * @param $roles array roles to grant
	 * @return boolean false on failure, true on success.
	 */
	public function grantRoles($id,Array $roles);
	/**
	 * Removes role privileges.
	 * @param $id string user id
	 * @param $roles array roles to revoke
	 * @return boolean false on failure, true on success.
	 */
	public function revokeRoles($id,Array $roles);
}
