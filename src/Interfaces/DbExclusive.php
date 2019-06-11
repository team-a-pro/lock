<?php

namespace TeamA\Lock\Interfaces;

use TeamA\Lock;

interface DbExclusive extends Db
{
    /**
     * Capture a lock in the context of the current lock object.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.
     *
     * @throws Lock\AlreadyLockedException if already locked
     * @throws Lock\TimeoutException if timeout exceeded
     */
    public function lock(int $timeout = null) : void;

    /**
     * Capture a lock without taking into account the context of the current lock object (at the connection level).
     * If a lock is already captured on the current connection, no exception will be thrown.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.
     *
     * @throws Lock\TimeoutException if timeout exceeded
     */
    public function lockQuietly(int $timeout = null) : void;

    /**
     * Whether the lock is captured by the current object and the current connection.
     *
     * In a single dB connection application, this also means whether the lock is captured by the current process.
     *
     * The lock may be lost if the connection is dropped. * If not specified, the default value will be used.
     */
    public function isLocked() : bool;

    /**
     * Whether the lock is captured by the current connection.
     *
     * In a single dB connection application, this also means whether the lock is captured by the current process.
     *
     * The lock may be lost if the connection is dropped.
     */
    public function isLockedByConnection() : bool;

    /**
     * Release a lock only if it is captured by the current lock object.
     *
     * The return value indicates whether the lock has actually been released.
     * The lock may have been missing or it may have become another lock object, in which case it will return false.
     */
    public function releaseIfLocked() : bool;

    /**
     * Release the lock regardless of where it was captured on the current connection.
     *
     * The return value indicates whether the lock has actually been released.
     * The lock could be missing, in this case it will return false.
     */
    public function forceRelease() : bool;
}