<?php
namespace Classes;

class CtrPath{

    protected static function dirfile()
    {
        return realpath(__DIR__ . "/../../../../");
    }

    protected static function dir(){
        return self::dirfile();
    }

    protected static function root_directory(){
        return self::dirfile();
    }

}
?>