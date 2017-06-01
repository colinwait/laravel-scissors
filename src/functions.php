<?php
namespace Colinwait\LaravelScissors;

if (!function_exists('safe_base64url_encode')) {
    function safe_base64url_encode($data)
    {
        $find = array('+', '/');
        $replace = array('-', '_');
        return str_replace($find, $replace, base64_encode($data));
    }
}

if (!function_exists('safe_base64url_decode')) {
    function safe_base64url_decode($str)
    {
        $find = array('-', '_');
        $replace = array('+', '/');
        return base64_decode(str_replace($find, $replace, $str));
    }
}