<?php
use Classes\Ctrx;
use Classes\Ctrql;
/**
 * setup limit request per minute
 * default is 10
 */
$limit_request_per_minute = 500;
Ctrx::x_rate_limit($limit_request_per_minute, 60, "ctrql_" . $action);
