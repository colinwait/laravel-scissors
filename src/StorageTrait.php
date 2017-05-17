<?php


namespace Colinwait\LaravelScissors;

use Illuminate\Support\Facades\DB;

trait StorageTrait
{
    protected function transData($img)
    {
        $img = [
            'key'        => strval($img->key),
            'width'      => intval($img->width),
            'height'     => intval($img->height),
            'filesize'   => intval($img->filesize),
            'mime'       => strval($img->mime),
            'dir'        => strval($img->dir),
            'filepath'   => strval($img->filepath),
            'origin_url' => strval($img->origin_url),
            'filename'   => strval($img->filename),
        ];

        return $img;
    }

    protected function storage($data)
    {
        if ($img = DB::table('scissors')->where('key', $data['key'])->first()) {
            return $this->transData($img);
        }

        if (!DB::table('scissors')->insert($data)) {
            throw new \Exception('Storage Image Info Failed');
        }

        $img = DB::table('scissors')->where('key', $data['key'])->first();

        return $this->transData($img);
    }

    protected function destroy($key)
    {
        return DB::table('scissors')->where('key', $key)->delete();
    }

    protected function show($key)
    {
        $img = DB::table('scissors')->where('key', $key)->first();

        return $this->transData($img);
    }
}