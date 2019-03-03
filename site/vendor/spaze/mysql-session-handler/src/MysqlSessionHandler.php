<?php
declare(strict_types = 1);

namespace Spaze\Session;

use Nette\Database\Context;
use SessionHandlerInterface;

/**
 * Storing session to database.
 * Inspired by: https://github.com/JedenWeb/SessionStorage/
 */
class MysqlSessionHandler implements SessionHandlerInterface
{

	/** @var string */
	private $tableName;

	/** @var integer */
	private $lockTimeout = 5;

	/** @var Context */
	private $context;

	/** @var string */
	private $lockId;

	/** @var string[] */
	private $idHashes = [];


	public function __construct(Context $context)
	{
		$this->context = $context;
	}


	public function setTableName(string $tableName): void
	{
		$this->tableName = $tableName;
	}


	public function setLockTimeout(int $timeout): void
	{
		$this->lockTimeout = $timeout;
	}


	private function hash(string $id, bool $rawOutput = true): string
	{
		if (!isset($this->idHashes[$id])) {
			$this->idHashes[$id] = hash('sha256', $id, true);
		}
		return ($rawOutput ? $this->idHashes[$id] : bin2hex($this->idHashes[$id]));
	}


	private function lock(): void
	{
		if ($this->lockId === null) {
			$this->lockId = $this->hash(session_id(), false);
			$this->context->query('SELECT GET_LOCK(?, ?) as `lock`', $this->lockId, $this->lockTimeout);
		}
	}


	private function unlock(): void
	{
		if ($this->lockId === null) {
			return;
		}

		$this->context->query('SELECT RELEASE_LOCK(?)', $this->lockId);
		$this->lockId = null;
	}


	/**
	 * @param string $savePath
	 * @param string $name
	 * @return boolean
	 */
	public function open($savePath, $name): bool
	{
		$this->lock();
		return true;
	}


	public function close(): bool
	{
		$this->unlock();
		return true;
	}


	/**
	 * @param string $sessionId
	 * @return boolean
	 */
	public function destroy($sessionId): bool
	{
		$hashedSessionId = $this->hash($sessionId);
		$this->context->table($this->tableName)->where('id', $hashedSessionId)->delete();
		$this->unlock();
		return true;
	}


	/**
	 * @param string $sessionId
	 * @return string
	 */
	public function read($sessionId): string
	{
		$this->lock();
		$hashedSessionId = $this->hash($sessionId);
		$row = $this->context->table($this->tableName)->get($hashedSessionId);

		if ($row) {
			return $row->data;
		}
		return '';
	}


	/**
	 * @param string $sessionId
	 * @param string $sessionData
	 * @return boolean
	 */
	public function write($sessionId, $sessionData): bool
	{
		$this->lock();
		$hashedSessionId = $this->hash($sessionId);
		$time = time();

		if ($row = $this->context->table($this->tableName)->get($hashedSessionId)) {
			if ($row->data !== $sessionData) {
				$row->update([
					'timestamp' => $time,
					'data' => $sessionData,
				]);
			} elseif ($time - $row->timestamp > 300) {
				// Optimization: When data has not been changed, only update
				// the timestamp after 5 minutes.
				$row->update([
					'timestamp' => $time,
				]);
			}
		} else {
			$this->context->table($this->tableName)->insert([
				'id' => $hashedSessionId,
				'timestamp' => $time,
				'data' => $sessionData,
			]);
		}

		return true;
	}


	/**
	 * @param integer $maxLifeTime
	 * @return boolean
	 */
	public function gc($maxLifeTime): bool
	{
		$maxTimestamp = time() - $maxLifeTime;

		// Try to avoid a conflict when running garbage collection simultaneously on two
		// MySQL servers at a very busy site in a master-master replication setup by
		// subtracting one tenth of $maxLifeTime (but at least one day) from $maxTimestamp
		// for each server with reasonably small ID except for the server with ID 1.
		//
		// In a typical master-master replication setup, the server IDs are 1 and 2.
		// There is no subtraction on server 1 and one day (or one tenth of $maxLifeTime)
		// subtraction on server 2.
		$serverId = $this->context->query('SELECT @@server_id as `server_id`')->fetch()->server_id;
		if ($serverId > 1 && $serverId < 10) {
			$maxTimestamp -= ($serverId - 1) * max(86400, $maxLifeTime / 10);
		}

		$this->context->table($this->tableName)
			->where('timestamp < ?', $maxTimestamp)
			->delete();

		return true;
	}

}
