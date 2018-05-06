<?php
/**
 * Interface AuthenticatorInterface | ZedBoot/System/Auth/AuthenticatorInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * User authentication
 * AuthenticatorInterface defines a model for authenticating user logins
 */
namespace ZedBoot\System\Auth;
interface AuthenticatorInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * Providing a separate function for this prevents the user name from getting into error logs if something goes wrong during authentication
	 */
	public function setName($name);
	/**
	 * Providing a separate function for this prevents the password from getting into error logs if something goes wrong during authentication
	 */
	public function setPassword($password);
	/**
	 * @return mixed user id on success, false on failure
	 */
	public function authenticate();
}
