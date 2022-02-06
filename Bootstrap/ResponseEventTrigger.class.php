<?php
namespace ZedBoot\Bootstrap;
/**
 * @license     GNU General Public License, version 3
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2022 Jonathan Hulka
 */
class ResponseEventTrigger implements ResponseEventTriggerInterface
{
	protected
		$handlers;

	public function __construct()
	{
		$this->handlers = [];
	}

	public function registerHandler(\ZedBoot\Bootstrap\ResponseEventHandlerInterface $handler) : void
	{
		foreach($this->handlers as $h) if($h === $handler) return;
		$this->handlers[] = $handler;
	}

	public function triggerInit() : void
	{
		foreach($this->handlers as $handler) $handler->responseInit();
	}

	public function triggerFinish() : void
	{
		foreach($this->handlers as $handler) $handler->responseFinish();
	}
}
