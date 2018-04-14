<?php
namespace ZedBoot\App;
interface ControllerInterface extends \ZedBoot\System\Error\ErrorReporterInterface
{
	public function update();
}
