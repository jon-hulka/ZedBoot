<?php
/**
 * Interface CookieInterface | ZedBoot/Session/CookieInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

namespace ZedBoot\Session;

/**
 * Cookie manager
 * Implementations of this interface should provide a consistent internal
 * id linked to a client's cookie. The internal id should not change when
 * the client's cookie value is regenerated.
 */
interface CookieInterface
{
	/**
	 * Most implementations will use $_COOKIE[$name], so this will be unnecessary,
	 * but useful if cookie needs to be set manually (such as in websocket bridge)
	 */
	public function setClientId(string $id);
	/**
	 * The id returned by this is only used internally - it is never sent to the client
	 * @param $create boolean if true and cookie doesn't exist, it will be created.
	 * @param $regenerate boolean if true, the cookie key will be regenerated.
	 * @return mixed null if cookie does not exist or is expired, internal cookie id otherwise (this value will not be affected by $regenerate)
	 */
	public function getId(bool $create=true,bool $regenerate=false): ?string;
	/**
	 * Clear the cookie.
	 */
	public function reset();
}
