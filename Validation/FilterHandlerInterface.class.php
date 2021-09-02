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
	public function applyFilter($value, string $name, array $options, ?array $flags = null);
	/**
	 * If applyFilter() returns a negative value, provides an optional custom error message.
	 */
	public function getMessage() : ?string;
}
