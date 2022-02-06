<?php
namespace ZedBoot\Bootstrap;
/**
 * @license     GNU General Public License, version 3
 * @author      Jonathan Hulka <jon.hulka@gmail.com>
 * @copyright   Copyright (c) 2022 Jonathan Hulka
 */
interface ResponseEventHandlerInterface
{
	public function responseInit() : void;
	public function responseFinish() : void;
}
