<?php
/**
 * Interface AccountInfoInterface | ZedBoot/System/Auth/AccountInfoInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Auth
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * User data retrieval
 * AccountInfoInterface defines a model for querying user information
 */
namespace ZedBoot\System\Auth;
interface AccountInfoInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	public function setSearchRoles(array $roles);
	public function setSearchIds(array $ids);
	public function setSearchNames(array $names);
	public function clearSearchParameters();
	/**
	 * @return Array ('id'=>...,'name'=>...,'roles'=>array(...),info=>array(...))
	 */
	public function getUsers();
}
