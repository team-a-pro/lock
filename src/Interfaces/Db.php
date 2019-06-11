<?php

namespace TeamA\Lock\Interfaces;

interface Db
{
    public const NO_TIMEOUT       =  0;
    public const INFINITY_TIMEOUT = -1;
}