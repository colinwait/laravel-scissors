<?php
namespace Colinwait\LaravelPockets;

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

if (!function_exists('site_material_config')) {
    function site_material_config()
    {
        $file = config('material.site_material_config_file');
        if (!\Illuminate\Support\Facades\File::exists($file)) {
            return [];
        }
        $site_config = json_decode(\Illuminate\Support\Facades\File::get($file), 1);
        if (!$site_config) {
            return [];
        }

        return $site_config;
    }
}