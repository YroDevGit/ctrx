<?php
namespace Classes;

class Datatype{

    //create a function here...
    static function is_string($any){
        return is_string($any);
    }

    static function is_int($any){
        return is_int($any);
    }

    static function is_float($any){
        return is_float($any);
    }

    static function is_array($any){
        return is_array($any);
    }

    static function is_number($any){
        return is_numeric($any);
    }

}
?>