<?php
/**
 * class SubSession | ZedBoot/Session/SubSessionFactory.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 - 2020 Jonathan Hulka
 */

/**
 * SessionFactoryInterface implementation
 * Decorates a SessionFactoryInterface instance.
 * Multiple SubSessionFactories can share a single SessionFactoryInterface without risk of key conflicts.
 */

namespace ZedBoot\Session;
class SubSessionFactory implements \ZedBoot\Session\SessionFactoryInterface
{
	protected
		$sessionFactory,
		$subPath,
		$expiry = null;

	/**
	 * @param \ZedBoot\Session\SessionInterface $session parent session
	 * @param string $subPath namespace to be used for generated SubSessions
	 * @param $expiry int|null optional expiry in seconds, if null parent session expiry will be used, if 0 no expiry
	 */
	public function __construct
	(
		\ZedBoot\Session\SessionFactoryInterface $sessionFactory,
		string $subPath,
		int $expiry = null
	)
	{
		$this->sessionFactory=$sessionFactory;
		$this->subPath=$subPath;
		$this->expiry = $expiry;
	}
	
	public function getSession($sessionId)
	{
		return new \ZedBoot\Session\SubSession($this->sessionFactory->getSession($sessionId), $this->subPath, $this->expiry);
	}
}
