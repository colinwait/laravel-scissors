<?php


namespace Colinwait\LaravelScissors;


interface ScissorInterface
{
    public function putFile($path, $key);

    public function fetch($url, $key);

    public function put($data, $key);

    public function delete($key);

    public function get($key);
}