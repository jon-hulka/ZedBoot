<?php
namespace ZedBoot\DI;
/**
 * Class DependencyLoaderException | ZedBoot/DI/DependencyLoaderException.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2020, Jonathan Hulka
 */

/**
 * Incorporates dependency chain in its output for easier debugging of dependency issues
 */
class DependencyLoaderException extends \Exception
{
	protected $dependencyChain;
	public function __construct(string $message, int $code=null, \Throwable $previous=null, array $dependencyChain=null)
	{
		parent::__construct($message,$code,$previous);
		$this->dependencyChain=$dependencyChain;
	}
	public function __toString()
	{
		$result='';
		$previous=$this->getPrevious();
		if($previous!==null) $result=': '.$previous;
		if($this->dependencyChain!==null && count($this->dependencyChain)>0) $result=': dependency chain: '.implode(' > ',$this->dependencyChain).$result;
		return $this->message.$result;
	}
}
