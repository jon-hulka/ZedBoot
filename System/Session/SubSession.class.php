<?php
/**
 * class SubSession | ZedBoot/System/Session/SubSession.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * SessionInterface implementation
 * Decorates a SessionInterface instance allowing multiple SubSession instances to use different key namespaces.
 */

namespace ZedBoot\System\Session;
class SubSession implements \ZedBoot\System\Session\SessionInterface
{
	protected
		$session=null,
		$expiry=null,
		$subPath=null;
	/**
	 * @param $session \ZedBoot\System\Session\SessionInterface
	 * @param $subPath String namespace to be prepended to every key
	 */
	public function __construct(\ZedBoot\System\Session\SessionInterface $session,$subPath)
	{
		$this->session=$session;
		$this->subPath=trim($subPath,'/');
	}
	public function setExpiry($seconds){ $this->expiry=$seconds; }
	public function getDataStore($key,$expiry=null)
	{
		if(empty($expiry)) $expiry=$this->expiry;
		return $this->session->getDataStore($this->subPath.'/'.trim($key,'/'),$expiry);
	}
	/**
	 * Use with caution, it affects all other attached subsessions
	 */
	public function gc($lifetime=null){ return $this->session->gc($lifetime); }
}
