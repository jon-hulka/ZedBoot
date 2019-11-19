<?php
/**
 * Interface FilterHandlerInterface | ZedBoot/Validation/FilterHandlerInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

namespace ZedBoot\Validation;
interface FilterHandlerInterface
{
	public function applyFilter($value,Array $options,$flags=null);
}
