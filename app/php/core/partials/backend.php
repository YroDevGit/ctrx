<?php
$transaction_active = false;

if (! function_exists("pdo")) {
    /** (Any) returns the value of the get */
    function pdo(string|null|array $db = null, $no_database = false)
    {
        static $pdo = null;
        try {
            if (is_array($db)) {
                $host = $db['host'] ?? "localhost";
                $user =  $db['user'] ?? "root";
                $pass = $db['password'] ?? "";
                $driver = $db['driver'] ?? "mysql";
                $dbname = $db['database'];
                if (! $dbname) {
                    add_sql_log("No database found. please check DB()", "be_errors");
                    error_response(["code" => "404", "status" => "notfound", "message" => "No database found. please check DB() "]);
                }

                if ($pdo == null) {
                    $pdo = new PDO("$driver:host=$host;dbname=$dbname", "$user", "$pass", [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                }
                return $pdo;
            }
            $host = getenv('dbhost');
            $user =  getenv('dbuser');
            $pass = getenv('dbpass');
            $dbname = $db == null ? getenv('database') : $db;
            if ($dbname == "" || $dbname == null) {
                add_sql_log("No database found. please check .env file", "be_errors");
                error_response(["code" => "404", "status" => "notfound", "message" => "No database found. please check .env file"]);
            }
            if ($pdo == null) {
                $dbdriver = getenv("dbdriver") == null ? "mysql" : getenv("dbdriver");
                $ddb = "dbname=$dbname";
                if ($no_database) {
                    $ddb = "";
                }
                $pdo = new PDO("$dbdriver:host=$host;$ddb", "$user", "$pass", [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
            return $pdo;
        } catch (PDOException $e) {
            add_sql_log($e->getMessage(), "error");
            error_response(["code" => getenv("error_code"), "status" => "PDO exception error", "message" => $e->getMessage()]);
        }
    }
}

if (!function_exists('execute_select')) {
    /**
     * Executes a SELECT query and returns a structured response.
     *Tyrone L Malocon
     * @param string $query   SQL with positional (?) or named (:name) placeholders
     * @param array<int|string, mixed> $params  Values to bind
     * @return array  Structured result with code, status, message, data, rowcount, lastquery
     */
    function execute_select(string $query, array $params = []): array
    {
        $stmt = null;
        try {
            $pdo  = pdo();
            $stmt = $pdo->prepare($query);

            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    return [
                        "code" => getenv('error_code'),
                        "status" => "error",
                        "message" => "Parameter cannot be an array: " . json_encode($value, JSON_UNESCAPED_UNICODE)
                    ];
                }

                $placeholder = is_int($key) ? $key + 1 : $key;
                $stmt->bindValue($placeholder, $value);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = $stmt->rowCount();
            $lastquery = $query;
            $stmt->closeCursor();
            $lastSQL = interpolate_query($lastquery, $params, "success");
            $firstrow = (!empty($results) ? true : false) == true ? $results[0] : [];

            $toret = [
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Query executed successfully",
                "data" => $results,
                "isempty" => empty($results) ? true : false,
                "hasresults" => !empty($results) ? true : false,
                "rowcount" => $count,
                "lastquery" => $lastSQL,
                "first_row" => $firstrow,
                "firstrow" => $firstrow
            ];
            add_sql_log("(SUCCESS) " . json_encode($toret), "info");

            return $toret;
        } catch (PDOException $e) {
            $lastSQL = interpolate_query($query, $params, "error");
            $err =  [
                "code" => getenv('error_code'),
                "status" => "error",
                "lastquery" => $lastSQL,
                "message" => "Database error: " . $e->getMessage()
            ];
            add_sql_log("(ERROR) " . json_encode($err), "info");
            add_sql_log("(ERROR) " . $e->getMessage(), "error");
            return $err;
        }
    }
}

if (! function_exists("execute_get")) {
    function execute_get(string $table, array $where = [], $columns = ['*']): array
    {
        $stmt = null;

        try {
            $pdo = pdo(); // your own PDO instance

            // Handle column selection
            if (is_array($columns)) {
                $columnList = implode(', ', $columns);
            } else {
                $columnList = $columns; // allow raw string like '*'
            }

            $query = "SELECT {$columnList} FROM {$table}";

            // Build WHERE clause
            $params = [];
            if (!empty($where)) {
                $whereClause = [];
                foreach ($where as $key => $value) {
                    $paramKey = ":" . $key;
                    $whereClause[] = "{$key} = {$paramKey}";
                    $params[$paramKey] = $value;
                }
                $query .= " WHERE " . implode(" AND ", $whereClause);
            }

            $stmt = $pdo->prepare($query);

            foreach ($params as $key => $value) {
                if (is_array($value)) {
                    $msg = "Parameter cannot be an array: " . json_encode($value, JSON_UNESCAPED_UNICODE);
                    add_sql_log("(ERROR) " . $msg, "error");
                    return [
                        "code" => getenv('error_code'),
                        "status" => "error",
                        "message" => $msg
                    ];
                }
                $stmt->bindValue($key, $value);
            }

            $stmt->execute();
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $count = $stmt->rowCount();
            $stmt->closeCursor();

            $lastSQL = interpolate_query($query, $params, "success");
            $firstrow = !empty($results) ? $results[0] : [];

            $toret = [
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Query executed successfully",
                "data" => $results,
                "isempty" => empty($results),
                "hasresults" => !empty($results),
                "rowcount" => $count,
                "lastquery" => $lastSQL,
                "first_row" => $firstrow,
                "firstrow" => $firstrow
            ];

            add_sql_log("(SUCCESS) " . json_encode($toret), "info");

            return $toret;
        } catch (PDOException $e) {
            $lastSQL = interpolate_query($query ?? 'INVALID SQL', $params ?? [], "error");
            $err = [
                "code" => getenv('error_code'),
                "status" => "error",
                "lastquery" => $lastSQL,
                "message" => "Database error: " . $e->getMessage()
            ];

            add_sql_log("(ERROR) " . json_encode($err), "info");
            add_sql_log("(ERROR) " . $e->getMessage(), "error");

            return $err;
        }
    }
}

if (! function_exists("execute_insert")) {

    function execute_insert(string $table, array $data): array
    {
        $columns = implode(", ", array_keys($data));
        $placeholders = implode(", ", array_fill(0, count($data), "?"));
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        $stmt = null;
        try {
            $pdo  = pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($data));
            $lastInsertId = $pdo->lastInsertId();
            $lastSQL = interpolate_query($sql, $data, "success");

            $rett = [
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Data inserted successfully",
                "lastquery" => $lastSQL,
                "id" => $lastInsertId,
                "rowcount" => 1,
                "data" => $data
            ];
            add_sql_log("(SUCCESS) " . json_encode($rett), "info");

            return $rett;
        } catch (PDOException $e) {
            $lastSql = interpolate_query($sql, $data, "error");
            $err = [
                "code" => getenv('error_code'),
                "status" => "error",
                "lastquery" => $lastSql,
                "message" => "Database error: " . $e->getMessage()
            ];
            add_sql_log("(ERROR) " . json_encode($err), "info");
            add_sql_log("(ERROR) " . $e->getMessage(), "error");
            return $err;
        }
    }
}

if (! function_exists("execute_update")) {
    function execute_update(string $table, array $data, array $where): array
    {
        $setClause = implode(", ", array_map(fn($col) => "$col = ?", array_keys($data)));
        $whereClause = implode(" AND ", array_map(fn($col) => "$col = ?", array_keys($where)));
        $sql = "UPDATE $table SET $setClause WHERE $whereClause";
        $params = array_merge(array_values($data), array_values($where));

        try {
            $pdo  = pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);

            $finalQuery = interpolate_query($sql, $params, "success");

            $rett = [
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Data updated successfully",
                "lastquery" => $finalQuery,
                "rowcount" => $stmt->rowCount(),
                "data" => $data
            ];

            add_sql_log("(SUCCESS) " . json_encode($rett), "info");
            return $rett;
        } catch (PDOException $e) {
            $finalQuery = interpolate_query($sql, $params, "error");
            $err = [
                "code" => getenv('error_code'),
                "status" => "error",
                "lastquery" => $finalQuery,
                "message" => "Database error: " . $e->getMessage()
            ];
            add_sql_log("(ERROR) " . json_encode($err), "info");
            add_sql_log("(ERROR) " . $e->getMessage(), "error");

            return $err;
        }
    }
}

if (! function_exists("execute_delete")) {
    function execute_delete(string $table, array $where): array
    {
        $stmt = "";
        $whereClause = implode(" AND ", array_map(fn($col) => "$col = ?", array_keys($where)));
        $sql = "DELETE FROM $table WHERE $whereClause";

        try {
            $pdo  = pdo();
            $stmt = $pdo->prepare($sql);
            $stmt->execute(array_values($where));
            $lastSQL = interpolate_query($sql, $where, "success");

            add_sql_log("(SUCCESS) " . json_encode([
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Data deleted successfully",
                "lastquery" => $lastSQL,
                "rowcount" => $stmt->rowCount(),
                "data" => $where
            ]), "info");

            return [
                "code" => getenv('success_code'),
                "status" => "success",
                "message" => "Data deleted successfully",
                "lastquery" => $lastSQL,
                "rowcount" => $stmt->rowCount(),
                "data" => $where
            ];
        } catch (PDOException $e) {
            $finalQuery = interpolate_query($sql, $where, "error");
            $err = [
                "code" => getenv('error_code'),
                "status" => "error",
                "lastquery" => $finalQuery,
                "message" => "Database error: " . $e->getMessage()
            ];
            add_sql_log("(ERROR) " . json_encode($err), "info");
            add_sql_log("(ERROR) " . $e->getMessage(), "error");
            return $err;
        }
    }
}

if (!function_exists('execute_query')) {
    /**
     * Execute any SQL statement with bound parameters.
     * Tyrone L Malocon
     * @param string                   $sql     SQL with positional (?) or named (:name) placeholders
     * @param array<int|string,mixed>  $params  Values to bind
     *
     * @return mixed  SELECT => array rows,
     *                INSERT => ['lastInsertId' => int|string, 'rowCount' => int],
     *                UPDATE/DELETE => int rowCount,
     *                other => bool|int (driver‑dependent)
     *
     * @throws PDOException|InvalidArgumentException
     */
    if (!function_exists('execute_query')) {
        /**
         * Execute any SQL command with parameters and structured response.
         */
        function execute_query(string $sql, array $params = [])
        {
            $stmt = null;
            try {
                $pdo  = pdo(); // Your own PDO helper
                $stmt = $pdo->prepare($sql);

                foreach ($params as $key => $value) {
                    if (is_array($value)) {
                        return [
                            "code" => getenv('error_code'),
                            "status" => "error",
                            "message" => "Parameter cannot be an array: " . json_encode($value, JSON_UNESCAPED_UNICODE)
                        ];
                    }
                    $placeholder = is_int($key) ? $key + 1 : $key;
                    $stmt->bindValue($placeholder, $value);
                }

                $stmt->execute();

                $verb = strtoupper(strtok(ltrim($sql), " \n\t("));
                $rett = [];
                switch ($verb) {
                    case 'SELECT':
                    case 'SHOW':
                    case 'DESCRIBE':
                    case 'PRAGMA':
                        $rett =  [
                            "code" => getenv('success_code'),
                            "status" => "success",
                            "message" => "Query executed successfully",
                            "rowcount" => $stmt->rowCount(),
                            "lastquery" => interpolate_query($sql, $params, "success"),
                            "hasresults" => $stmt->rowCount() > 0 ? true : false,
                            "isempty" => $stmt->rowCount() == 0 ? true : false,
                            "data" => $stmt->fetchAll(PDO::FETCH_ASSOC)
                        ];
                        break;

                    case 'INSERT':
                        $rett = [
                            'code' => getenv('success_code'),
                            'status' => 'success',
                            'message' => 'Data inserted successfully',
                            "lastquery" => interpolate_query($sql, $params, "success"),
                            'id' => $pdo->lastInsertId(),
                            'rowcount' => $stmt->rowCount(),
                            'data' => $params
                        ];
                        break;

                    case 'UPDATE':
                        $rett = [
                            'code' => getenv('success_code'),
                            'status' => 'success',
                            'message' => 'Data updated successfully',
                            "lastquery" => interpolate_query($sql, $params, "success"),
                            'rowcount' => $stmt->rowCount(),
                            'msg' => $stmt->rowCount() == 0 ? "Success but no data affected" : "Data Updated Successfully",
                        ];
                        break;

                    case 'DELETE':
                        $rett = [
                            'code' => getenv('success_code'),
                            'status' => 'success',
                            'message' => 'Data deleted successfully',
                            'lastquery' => interpolate_query($sql, $params, "success"),
                            'rowcount' => $stmt->rowCount(),
                            'msg' => $stmt->rowCount() == 0 ? "Success but no data affected" : "Data Deleted Successfully",
                        ];
                        break;

                    default: // CREATE, DROP, etc.
                        $rett = [
                            'code' => getenv('success_code'),
                            'status' => 'success',
                            'message' => "$verb command executed",
                            "lastquery" => interpolate_query($sql, $params, "success"),
                            'rowcount' => $stmt->rowCount()
                        ];
                }

                $stmt->closeCursor();
                $stmt = null;
                $toret = json_encode($rett);
                add_sql_log("(SUCCESS) " . $toret, "info");
                return $rett;
            } catch (PDOException $e) {
                $lastSQL = interpolate_query($sql, $params, "error");
                $rett = [
                    "code" => getenv('error_code'),
                    "status" => "error",
                    "lastquery" => $lastSQL,
                    "message" => "Database error: " . $e->getMessage(),

                ];
                add_sql_log("(ERROR) " . json_encode($rett), "info");
                add_sql_log("(ERROR) " . $e->getMessage(), "error");
                return $rett;
            }
        }
    }
}

function db_start()
{ //Previous name: start_transaction
    global $transaction_active;
    $pdo = pdo();
    if (!$pdo->inTransaction()) {
        $pdo->beginTransaction();
        $transaction_active = true;
    }
}

function db_commit()
{ // Previous name: commit_transaction
    global $transaction_active;
    if ($transaction_active) {
        $pdo = pdo();
        $pdo->commit();
        $transaction_active = false;
    }
}

function db_rollback()
{ // rollback_transaction
    global $transaction_active;
    if ($transaction_active) {
        $pdo = pdo();
        $pdo->rollBack();
        $transaction_active = false;
    }
}