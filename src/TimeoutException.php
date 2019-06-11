<?php

namespace TeamA\Lock;

/**
 * throws if lock timeout exceeded
 *
 * Should always be jammed in the catch block.
 */
class TimeoutException extends \Exception
{
    public function __construct(string $className, int $timeout, bool $exclusive = true)
    {
        $message = "Lock $className timeout ({$timeout}s) exceeded in ";

        $message .= $exclusive ? 'exclusive (write) mode' : 'read mode';

        parent::__construct($message);
    }
}