<?php
namespace ZedBoot\Bootstrap;
/**
 * @license     GNU General Public License, version 3
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2022 Jonathan Hulka
 */
interface ResponseEventTriggerInterface
{
	public function registerHandler(\ZedBoot\Bootstrap\ResponseEventHandlerInterface $handler) : void;
	public function triggerInit() : void;
	public function triggerFinish() : void;
}
