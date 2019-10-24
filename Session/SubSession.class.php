<?php
/**
 * class SubSession | ZedBoot/Session/SubSession.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2019 Jonathan Hulka
 */

/**
 * SessionInterface implementation
 * Decorates a SessionInterface instance allowing multiple SubSession
 * instances to share a single SessionInterface without risk of key
 * collisions.
 */

namespace ZedBoot\Session;
use \ZedBoot\Error\ZBError as Err;
class SubSession implements \ZedBoot\Session\SessionInterface
{
	protected
		$session=null,
		$expiry=null,
		$subPath=null;
	/**
	 * @param $session \ZedBoot\Session\SessionInterface
	 * @param $subPath String namespace to be prepended to every key
	 */
	public function __construct(\ZedBoot\Session\SessionInterface $session,$subPath)
	{
		$this->session=$session;
		$this->subPath=trim($subPath,'/');
	}
	public function getDataStore($key,$expiry=null,$forceCreate=true)
	{
		if(empty($expiry)) $expiry=$this->expiry;
		return $this->session->getDataStore($this->subPath.'/'.trim($key,'/'),$expiry,$forceCreate);
	}
	public function clearAll($keyRoot='')
	{
		$this->session->clearAll($this->subPath.'/'.trim($keyRoot,'/'));
	}
}
