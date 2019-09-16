<?php
/**
 * Interface DependencyLoaderInterface | ZedBoot/DI/DependencyLoaderInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     DI
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2017, 2018, Jonathan Hulka
 */

/**
 * Dependency loader
 * Implementations load services and parameters
 */
namespace ZedBoot\DI;
interface DependencyLoaderInterface
{
	/**
	 * @param string $id parameter id
	 * @param string|null $type optional expected result type, could be class name or any expected result from gettype()
	 * @return mixed loaded dependency
	 */
	public function getDependency($id,$type=null);
}
