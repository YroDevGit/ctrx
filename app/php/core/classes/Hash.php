<?php

namespace Classes;

class Hash
{

    public static function encode(String $string, int $size = 16)
    {
        $secret = env("hash_secret");
        return substr(md5($secret . $string), 0, $size);
    }

    public static function verify(String $string, String $hash)
    {
        $hashed1 = self::encode($string);
        return $hashed1 === $hash;
    }

    public static function hash(String $text, $length = 16)
    {
        return substr(md5($text), 0, $length);
    }

    public static function encrypt($string, string|null $key = null)
    {
        return encrypt($string, $key);
    }

    public static function decrypt($string, string|null $key = null)
    {
        return decrypt($string, $key);
    }

    public static function error_save_decryption_error(){
        \Classes\Ctrx::x_rate_limit(3, 60, "/ctrx@decryption/route@ctrx10050714/ctrraz/10046331/ctrxyro");
        throw new \Exception("Decryption error.!");
    }
}