<?php
/**
 * Interface ErrorReporterInterface | ZedBoot/System/Error/ErrorReporterInterface.class.php
 * @license     GNU General Public License, version 3
 * @package     System
 * @subpackage  Error
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2016-2018 Jonathan Hulka
 */

/**
 * Error reporter
 * Provides a consistent error handling mechanism
 */

namespace ZedBoot\System\Error;
interface ErrorReporterInterface
{
	public function getError();
}
