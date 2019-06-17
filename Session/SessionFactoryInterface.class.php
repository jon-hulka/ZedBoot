<?php
/**
 * Interface SessionFactoryInterface | ZedBoot/Session/SessionFactoryInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * Allows creation of SessionInterface instances at run-time after session id is known.
 */
namespace ZedBoot\Session;
interface SessionFactoryInterface
{
	public function getSession($sessionId);
}
