<?php
/**
 * Interface FilterInterface | ZedBoot/Validation/FilterInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019 Jonathan Hulka
 */

/**
 * Input validation filter
 */

namespace ZedBoot\Validation;
interface FilterInterface
{
	/**
	 * @param array $parameters values to be filtered
	 * 
	 * @return array ['status' => 'success', 'data' => [...]] or ['status' => 'error', 'messages' => [...]]
	 */
	public function validate(Array $parameters) : array;
}
