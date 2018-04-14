<?php
namespace ZedBoot\App;
interface ViewInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	/**
	 * If anything can break, it should be done here
	 * @return boolean error status
	 */
	public function init();
	/**
	 * Only for setting response headers and writing output
	 * @return void
	 */
	public function output();
}
