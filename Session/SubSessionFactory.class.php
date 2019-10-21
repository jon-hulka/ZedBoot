<?php
/**
 * class SubSession | ZedBoot/Session/SubSessionFactory.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

/**
 * SessionFactoryInterface implementation
 * Decorates a SessionFactoryInterface instance. Multiple
 * SubSessionFactories can share a single SessionFactoryInterface 
 * without risk of key conflicts.
 */

namespace ZedBoot\Session;
class SubSessionFactory implements \ZedBoot\Session\SessionFactoryInterface
{
	protected
		$sessionFactory,
		$subPath;
	/**
	 * @param $session \ZedBoot\Session\SessionInterface
	 * @param $subPath String namespace to be used for generated SubSessions
	 */
	public function __construct(\ZedBoot\Session\SessionInterface $sessionFactory,$subPath)
	{
		$this->sessionFactory=$sessionFactory;
		$this->subPath=$subPath;
	}
	
	public function getSession($sessionId)
	{
		return new \ZedBoot\Session\SubSession($this->sessionFactory->getSession($sessionId),$this->subPath);
	}
}
