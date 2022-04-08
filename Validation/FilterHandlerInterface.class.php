<?php
/**
 * Interface FilterHandlerInterface | ZedBoot/Validation/FilterHandlerInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     Validation
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2019, 2021 Jonathan Hulka
 */

namespace ZedBoot\Validation;
interface FilterHandlerInterface
{
	/**
	 * @return                           <br>
	 * [                                 <br>&emsp;
	 *   'status' => 'error',            <br>&emsp;
	 *   //Optional - if empty, the default message will be used.<br>&emsp;
	 *   'message' => (string)           <br>
	 * ]                                 <br>
	 * OR                                <br>
	 * [                                 <br>&emsp;
	 *   'status' => 'success',          <br>&emsp;
	 *   'value' => (filter result)      <br>
	 * ]
	 * 
	 */
	public function applyFilter(string $value, string $name, array $options, ?array $flags = null) : array;
}
