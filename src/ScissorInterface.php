<?php


namespace Colinwait\LaravelScissors;


interface ScissorInterface
{
    public function putFile($path, $key);

    public function fetch($url, $key);
}