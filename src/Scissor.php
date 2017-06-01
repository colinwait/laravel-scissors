<?php


namespace Colinwait\LaravelScissors;

use Illuminate\Support\Facades\Facade;

class Scissor extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'scissor';
    }
}