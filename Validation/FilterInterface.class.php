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
	 * @param Array $parameters values to be filtered
	 * @return Array|boolean filtered results on success, false on failure.
	 */
	public function validate(Array $parameters);
	/**
	 * @return Array error messages from last call to validate, by parameter keys.
	 */
	public function getMessages();
}
