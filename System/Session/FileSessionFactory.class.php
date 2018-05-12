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
interface SessionFactoryInterface implements \ZedBoot\System\Error\ErrorReporterInterface
{
	protected
		$savePath=null,
		$expiry=null,
		$gcChance=null,
		$error=null;
	public function __construct($savePath,$expiry=null,$gcChance=null)
	{
		$this->savePath=$savePath;
		$this->expiry=$expiry;
		$this->gcChance=$gcChance;
	}
	public function getSession($sessionId)
	{
		$result=false;
		try
		{
			$result=new \ZedBoot\System\Session\FileSession($this->savePath,$sessionId,$this->expiry,$this->gcChance);
		}
		catch(\Exception $e)
		{
			error_log($e);
			$this->error=$e->getMessage();
		}
		return $result;
	}
}
