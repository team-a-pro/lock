<?php

namespace TeamA\Lock;

abstract class AbstractDbExtended extends AbstractDb implements Interfaces\DbExtended
{
    /**
     * Default lock timeout.
     * -1 - wait forever for the lock (more precisely, until the script is finished)
     * Can be overridden in successor classes.
     */
    public const DEFAULT_READ_TIMEOUT  = self::NO_TIMEOUT;
    public const DEFAULT_WRITE_TIMEOUT = self::INFINITY_TIMEOUT;

    public const NAMESPACE = 'masterExtended';

    /**
     * Grab the lock in read mode.
     *
     * Multiple connections can capture a lock with the same name if it is not captured in write mode.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.
     * Can be overridden in successor classes.
     *
     * @throws TimeoutException if timeout exceeded
     */
    public function lockRead(int $timeout = null) : void
    {
        $this->_getLock($timeout ?? self::DEFAULT_READ_TIMEOUT, false);
    }

    /**
     * Grab the lock in write mode.
     *
     * This capture is possible if the lock is not captured in read or write mode on another connection.
     * Otherwise in case the connection will wait for the lock release.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.* If not specified, the default value will be used.
     * Can be overridden in successor classes.
     *
     * @throws TimeoutException if timeout exceeded
     */
    public function lockWrite(int $timeout = null) : void
    {
        $this->_getLock($timeout?? self::DEFAULT_WRITE_TIMEOUT, true);
    }

    /**
     * Release the lock.
     */
    public function release() : void
    {
        $this->_query('SELECT service_release_locks(:namespace)', [
            ':namespace' => $this->getKey()
        ]);
    }

    /**
     * @throws TimeoutException if timeout exceeded
     */
    final private function _getLock(int $timeout, bool $writeMode) : void
    {
        $udf = $writeMode ? 'service_get_write_locks' : 'service_get_read_locks';

        $key = $namespace = $this->getKey();

        $result = null;

        try {
            $result = (bool) (int) $this->_query("SELECT $udf(:namespace, :key, :timeout)", [
                ':namespace' => $namespace,
                ':key'       => $key,
                ':timeout'   => $timeout
            ]);
        } catch (\Exception $e) {
            $this->_convertLockException($e, $timeout, $writeMode);
        }

        if (!$result) {
            throw new TimeoutException(static::class, $timeout, $writeMode);
        }
    }
}