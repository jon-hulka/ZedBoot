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
 * Incorporates dependency chain into error messag for easier debugging of dependency issues
 */
class DependencyLoaderException extends \Exception
{
	protected $dependencyChain;
	public function __construct(string $message, int $code=null, \Throwable $previous=null, array $dependencyChain=null)
	{
		parent::__construct
		(
			$message.($dependencyChain?': dependency chain: '.implode(' > ',$dependencyChain):'').($previous?': '.$previous->getMessage():''),
			$code,
			$previous
		);
		$this->dependencyChain=$dependencyChain;
	}
}
