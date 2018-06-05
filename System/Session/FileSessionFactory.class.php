<?php
/**
 * Class FileSessionFactory | ZedBoot/System/Session/FileSessionFactory.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

namespace ZedBoot\System\Session;
use \ZedBoot\System\Error\ZBError as Err;
class FileSessionFactory implements \ZedBoot\System\Session\SessionFactoryInterface
{
	protected
		$savePath=null,
		$expiry=null,
		$gcChance=null;
	public function __construct($savePath,$expiry=null,$gcChance=null)
	{
		$this->savePath=$savePath;
		$this->expiry=$expiry;
		$this->gcChance=$gcChance;
	}
	public function getSession($sessionId)
	{
		return new \ZedBoot\System\Session\FileSession($this->savePath,$sessionId,$this->expiry,$this->gcChance);
	}
}
