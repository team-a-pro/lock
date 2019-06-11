<?php

namespace TeamA\Lock\Interfaces;

use TeamA\Lock;

interface DbExtended extends Db
{
    /**
     * Grab the lock in read mode.
     *
     * Multiple connections can capture a lock with the same name if it is not captured in write mode.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.
     *
     * @throws Lock\TimeoutException if timeout exceeded
     */
    public function lockRead(int $timeout = null) : void;

    /**
     * Grab the lock in write mode.
     *
     * This capture is possible if the lock is not captured in read or write mode on another connection.
     *
     * @var int $timeout - how long in seconds to wait for the lock.
     * If not specified, the default value will be used.
     *
     * @throws Lock\TimeoutException if timeout exceeded
     */
    public function lockWrite(int $timeout = null) : void;

    /**
     * Release the lock.
     */
    public function release() : void;
}