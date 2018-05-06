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
interface AccountManagerInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * @param $name String unique user name
	 * @param $info Array user information such as first and last name, etc
	 * @param $roles Array user permissions
	 * @return mixed false on error, user id on success
	 */
	public function createUser($name,Array $info,Array $roles);
	public function deleteUser($id);
	/**
	 * To be used as a preparation for changePassword()
	 */
	public function setNewPassword($password);
	/**
	 * To be used after setNewPassword
	 */
	public function changePassword($id);
	public function setInfo($id,Array $info);
	public function grantRoles($id,Array $roles);
	public function revokeRoles($id,Array $roles);
}
