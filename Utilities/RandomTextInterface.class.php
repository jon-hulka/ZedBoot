<?php
/**
 * Interface RandomTextInterface | ZedBoot/Utilities/RandomTextInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Utilities
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2020 - 2021 Jonathan Hulka
 */
namespace ZedBoot\Utilities;
/**
 * Random text generator
 */
interface RandomTextInterface
{
	/**
	 * @param int $length string length of required result
	 * @return string random text
	 */
	public function get(int $length) : string;
}
