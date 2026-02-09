<?php
namespace Classes;

class Logs{

    static function add($message, string $intro=""){
        my_log($message, $intro);
    }

    static function my_log($message, string $intro=""){
        self::add($message, $intro);
    }

    static function sql_logs($message, string $intro=""){
        add_sql_log($message, "sql_logs",$intro);
    }

    static function sql_errors($message, string $intro=""){
        add_sql_log($message, "sql_errors", $intro);
    }

    static function query_logs($message, string $intro="") {
        add_sql_log($message, "query_logs", $intro);
    }

    static function be_errors($message, string $intro=""){
        add_sql_log($message, "be_errors", $intro);
    }

}
?>