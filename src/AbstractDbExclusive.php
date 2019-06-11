<?php

namespace TeamA\Lock;

/**
 * Named lock in the DBMS. This blocking works outside the context of transactions.
 *
 * If a lock with the same name is captured by someone else, the process will wait for the lock to be released
 * immediately after calling the lock method.
 *
 * Warning! The same connection can create many locks with the same name.
 *
 * @see http://dev.mysql.com/doc/refman/5.7/en/miscellaneous-functions.html#function_get-lock
 * @see http://dev.mysql.com/doc/refman/5.7/en/locking-service.html
 */
abstract class AbstractDbExclusive extends AbstractDb implements Interfaces\DbExclusive
{
    /**
     * Default lock timeout.
     * -1 - wait forever for the lock (more precisely, until the script is finished)
     * Can be overridden in successor classes.
     */
    const DEFAULT_TIMEOUT = self::INFINITY_TIMEOUT;

    public const NAMESPACE = 'masterExclusive';

    /**
     * @var bool
     */
    private $_isLocked = false;

    /**
     * @throws AlreadyLockedException
     * @throws TimeoutException
     */
    final public function lock(int $timeout = null) : void
    {
        if ($this->isLockedByConnection()) {
            throw new AlreadyLockedException();
        }

        $timeout = $timeout ?? static::DEFAULT_TIMEOUT;

        $result = null;

        try {
            $this->_isLocked = (bool) (int) $this->_query('SELECT GET_LOCK(:key, :timeout)', [
                ':key'     => $this->getKey(),
                ':timeout' => $timeout
            ]);
        } catch (\Exception $e) {
            $this->_convertLockException($e, $timeout);
        }

        if (!$this->_isLocked) {
            throw new TimeoutException(static::class, $timeout);
        }
    }

    final public function lockQuietly(int $timeout = null) : void
    {
        if (!$this->isLockedByConnection()) {
            $this->lock($timeout);
        }
    }

    final public function isLocked() : bool
    {
        return $this->_isLocked && $this->isLockedByConnection();
    }

    final public function isLockedByConnection() : bool
    {
        return $this->_getUsedConnectionId() === $this->_getConnectionId();
    }

    final public function forceRelease() : bool
    {
        $result = (bool) (int) $this->_query('SELECT RELEASE_LOCK(:key)', [':key' => $this->getKey()]);

        $this->_isLocked = false;

        return $result;
    }

    final public function releaseIfLocked() : bool
    {
        $result = false;

        if ($this->_isLocked) {
            if ($this->isLockedByConnection()) {
                $result = $this->forceRelease();
            }

            $this->_isLocked = false;
        }

        return $result;
    }

    final private function _getConnectionId() : string
    {
        return $this->_query('SELECT CONNECTION_ID()');
    }

    final private function _getUsedConnectionId() : ? string
    {
        return $this->_query('SELECT IS_USED_LOCK(:key)', [':key' => $this->getKey()]);
    }
}