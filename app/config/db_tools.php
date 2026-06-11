<?php
/** This is only tested only in MariaDB mysql database
 * This file blocks users to use db tools
 * make a logic to filter users that can access db tool
 * /ctrxtools/db
 * Ctrx::use_db_tools(); // use this to activate db tools
 */
use Classes\Ctrx;

Ctrx::forbidden_page();

