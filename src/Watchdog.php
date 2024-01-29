<?php

namespace Blackjack200\Watchdog;

use pocketmine\Server;
use pocketmine\thread\log\ThreadSafeLogger;
use pocketmine\thread\Thread;
use pocketmine\utils\Process;

class Watchdog extends Thread {
	private const MAX_MAIN_THREAD_HUNG_UP_SEC = 10;
	private float $lastHeartbeatSec;
	private ThreadSafeLogger $logger;

	public function __construct() {
		$this->logger = Server::getInstance()->getLogger();
		$this->mainThreadHeartbeat();
	}

	public function mainThreadHeartbeat() : void {
		$this->synchronized(function() : void {
			$this->lastHeartbeatSec = microtime(true);
			$this->notify();
		});
	}

	protected function onRun() : void {
		while (!$this->isKilled) {
			$this->synchronized(function() : void {
				$secSinceLastHeartbeat = microtime(true) - $this->lastHeartbeatSec;
				if ($secSinceLastHeartbeat >= self::MAX_MAIN_THREAD_HUNG_UP_SEC) {
					$this->logger->alert(str_repeat("-", 40));
					$this->logger->alert("Server stopped responding ({$secSinceLastHeartbeat}s).");
					$this->logger->alert("Terminating the server.");
					$this->logger->alert(str_repeat("-", 40));
					Process::kill(Process::pid(), true);
					posix_kill(getmypid(), SIGKILL);
				}
			});
			$this->synchronized(function() : void {
				if (!$this->isKilled) {
					$this->wait(1000 * self::MAX_MAIN_THREAD_HUNG_UP_SEC);
				}
			});
		}
	}
}