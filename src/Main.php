<?php

declare(strict_types=1);

namespace Blackjack200\Watchdog;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;

class Main extends PluginBase {
	private Watchdog $thread;

	protected function onEnable() : void {
		$this->thread = new Watchdog();
		$this->thread->start();
		$this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->thread->mainThreadHeartbeat()), 1);
	}

	protected function onDisable() : void {
		$this->thread->quit();
	}
}
