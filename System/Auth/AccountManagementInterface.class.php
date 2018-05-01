<?php
/**
 * Interface AccountManagementInterface | ZedBoot/System/Bootstrap/AccountManagementInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * User creation and management
 * AccountManagementInterface defines a model for managing user accounts
 */
namespace ZedBoot\System\Auth;
interface AccountManagementInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * @param $name String unique user name
	 * @param $info Array user information such as first and last name, etc
	 * @param $roles Array user permissions
	 * @return mixed user id on success, false on failure
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
	public function changePassword();
	public function setInfo($id,Array $info);
	public function addRoles($id,Array $roles);
	public function revokeRoles($id,Array $roles);
}
