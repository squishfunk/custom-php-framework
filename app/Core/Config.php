<?php 

namespace App\Core;

class Config {

    private static array $config;

    public static function load(string $dir): void
    {
        self::$config = require $dir;
    }

    public static function get(string $key): mixed
    {
        $keys = explode('.', $key);

        $value = self::$config;

        foreach($keys as $_key){
            if(isset($value[$_key])){
                $value = $value[$_key];
            } else {
                return null;
            }
        }

        return $value;
    }
}