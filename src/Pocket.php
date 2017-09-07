<?php


namespace Colinwait\LaravelPockets;

use Illuminate\Support\Facades\Facade;

class Pocket extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'pocket';
    }
}