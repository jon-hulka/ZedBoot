<?php
/**
 * Interface CookieInterface | ZedBoot/System/Session/CookieInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

/**
 * Cookie manager
 * Implementations of this interface should provide a consistent internal
 * id linked to a client's cookie. The internal id should not change when
 * the client's cookie value is regenerated.
 */
namespace ZedBoot\System\Session;
interface CookieInterface
{
	/**
	 * @param $create boolean if true and cookie doesn't exist, it will be created.
	 * @param $regenerate boolean if true, the cookie key will be regenerated.
	 * @return mixed null if cookie does not exist or is expired, internal cookie id otherwise (this value will not be affected by $regenerate)
	 */
	public function getId($create=true,$regenerate=false);
	/**
	 * Clear the cookie.
	 */
	public function reset();
}
