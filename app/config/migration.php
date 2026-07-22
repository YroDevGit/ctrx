<?php
use Classes\Migration;

Migration::table_ts("logs", [
    "id" => "@primary",
    "message" => ["varchar"=>800],
    "status" => ["int"=>11, "default" => 1]
]);