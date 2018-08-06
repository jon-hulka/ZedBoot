<?php
/**
 * Interface DependencyLoaderInterface | ZedBoot/System/DI/DependencyLoaderInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, 2018, Jonathan Hulka
 */

/**
 * Dependency loader
 * Implementations load services and parameters
 */
namespace ZedBoot\System\DI;
interface DependencyLoaderInterface
{
	/**
	 * @param string $id parameter id
	 * @param string|null $classType optional expected result type
	 * @return mixed loaded dependency
	 */
	public function getDependency($id,$classType=null);
}
