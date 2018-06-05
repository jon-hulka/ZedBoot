<?php
/**
 * Interface SessionFactoryInterface | ZedBoot/System/Session/SessionFactoryInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * Allows creation of SessionInterface instances at run-time after session id is known.
 */
namespace ZedBoot\System\Session;
interface SessionFactoryInterface
{
	public function getSession($sessionId);
}
