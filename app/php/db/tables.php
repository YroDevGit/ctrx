<?php
use Classes\Migration;

Migration::table_ts("translations", [
    "id" => "@primary",
    "lang" => "text",
    "name" => "varchar",
    "en" => "text",
    "str" => "text",
], true);

Migration::table_ts("logs", [
    "id" => "@primary",
    "message" => ["varchar"=>800],
    "status" => ["int"=>11, "default" => 1]
]);