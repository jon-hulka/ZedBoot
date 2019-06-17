<?php
/**
 * Class FileSessionFactory | ZedBoot/Session/FileSessionFactory.class.php
 * @license     GNU General Public License, version 3
 * @package     Session
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2018 Jonathan Hulka
 */

namespace ZedBoot\Session;
use \ZedBoot\Error\ZBError as Err;
class FileSessionFactory implements \ZedBoot\Session\SessionFactoryInterface
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
		return new \ZedBoot\Session\FileSession($this->savePath,$sessionId,$this->expiry,$this->gcChance);
	}
}