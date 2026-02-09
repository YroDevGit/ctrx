<?php

namespace Classes;

use Exception;

/**
 * This is Basixs BaseTable Extension
 * this is like table models ORM
 * @Author: Tyrone Malocon
 */
class BaseTable
{
    protected $pdo;
    protected $table;
    protected $create_date;
    protected $update_date;
    protected $fillable = [];
    protected $guarded = [];
    protected $timestamps = true;
    protected $hidden = [];
    private static $rowcount;
    private static $lastQuery;
    private static $lastBindings;
    private static $totalRecords;
    private static $totalPages;
    private static $currentPage;
    private static $isActive = 3;

    protected $attributes = [];

    public function __construct(array $attributes = [])
    {
        $this->pdo = pdo();
        $this->attributes = $attributes;
    }

    public function __get($key)
    {
        return $this->attributes[$key] ?? null;
    }

    public function __set($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public static function table()
    {
        $self = static::instance();
        return $self->table;
    }

    static function tbl()
    {
        return self::table();
    }

    protected function filterFillable(array $data): array
    {
        if (!empty($this->fillable)) {
            return array_filter(
                $data,
                fn($key) => in_array($key, $this->fillable),
                ARRAY_FILTER_USE_KEY
            );
        }

        if (!empty($this->guarded)) {
            return array_filter(
                $data,
                fn($key) => !in_array($key, $this->guarded),
                ARRAY_FILTER_USE_KEY
            );
        }

        return $data;
    }

    protected function hydrate(array $row): array
    {
        return array_diff_key($row, array_flip($this->hidden));
    }

    public static function filterHidden(array $data, array $hiddenKeys = []): array
    {
        if (empty($hiddenKeys)) {
            return $data;
        }
        return array_diff_key($data, array_flip($hiddenKeys));
    }

    protected static function instance(array $attributes = [])
    {
        return new static($attributes);
    }

    public static function all(array $options = [])
    {
        $self = static::instance();

        $sql = "SELECT * FROM `{$self->table}`";
        $bindings = [];

        if (isset($options['where']) && is_array($options['where'])) {
            $whereParts = [];
            foreach ($options['where'] as $col => $val) {
                $whereParts[] = "`$col` = ?";
                $bindings[] = $val;
            }
            if ($whereParts) {
                $sql .= " WHERE " . implode(" AND ", $whereParts);
            }
        }

        if (isset($options['group by'])) $sql .= " GROUP BY " . $options['group by'];
        if (isset($options['order by'])) $sql .= " ORDER BY " . $options['order by'];
        if (isset($options['limit'])) $sql .= " LIMIT " . (int)$options['limit'];
        if (isset($options['offset'])) $sql .= " OFFSET " . (int)$options['offset'];

        self::$lastQuery = $sql;
        self::$lastBindings = $bindings;

        $stmt = $self->pdo->prepare($sql);
        $stmt->execute($bindings);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        self::$rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        return array_map([$self, 'hydrate'], $rows);
    }

    public static function findOne(array $where)
    {
        $self = static::instance();
        $data = $self->find($where);
        return $data[0] ?? [];
    }

    public static function count(array|null $where = null, array|int|null $extra = null): int|null
    {
        $find = [];
        if (is_null($where)) {
            $find = self::getAll();
        } else {
            $find = self::find($where, $extra);
        }
        return sizeof($find);
    }

    public static function count_pages(array|null $where = null, array|int|null $extra = null, $size = 10){
        $count = self::count($where, $extra);
        $pages = ceil($count / $size);
        return $pages;
    }

    /**
     * Initialize current table to fetch only
     * if: true / 1 -> display all active = 1
     * if: false / 0 -> display all active = 0
     */
    public static function init_active(int|bool $active)
    {
        if (is_bool($active)) {
            if ($active) {
                self::$isActive = 1;
            } else {
                self::$isActive = 0;
            }
        } else {
            self::$isActive = $active;
        }
    }

    public static function get(array $where, array $extra = []): array
    {
        $self = static::instance();
        $bindings = [];
        $sql = "SELECT * FROM `{$self->table}`";

        if ($self::$isActive != 3) {
            if ($self::$isActive == 0) {
                $where['active'] = 0;
            } else {
                $where['active'] = 1;
            }
        }

        if (!empty($where)) {
            [$whereClause, $bindings] = $self->buildWhere($where);
            $sql .= " WHERE $whereClause";
        }

        if (isset($extra['group by'])) $sql .= " GROUP BY " . $extra['group by'];
        if (isset($extra['order by'])) $sql .= " ORDER BY " . $extra['order by'];
        if (isset($extra['limit'])) $sql .= " LIMIT " . (int)$extra['limit'];
        if (isset($extra['offset'])) $sql .= " OFFSET " . (int)$extra['offset'];

        self::$lastQuery = $sql;
        self::$lastBindings = $bindings;

        $stmt = $self->pdo->prepare($sql);
        foreach ($bindings as $key => $val) {
            $stmt->bindValue($key, $val);
        }
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        self::$rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        return $rows ? array_map([$self, 'hydrate'], $rows) : [];
    }

    public static function getAll(array|null $where = null, array|int|null $extra = null)
    {
        $extra = $extra ?? [];
        if (is_null($where) || empty($where)) {
            $all = self::select(null, $extra);
            return $all ?? [];
        }
        return self::get($where, $extra);
    }

    public static function find(array $where, array|int|null $extra = null)
    {
        $self = static::instance();
        if (!is_array($where)) {
            throw new \InvalidArgumentException("Where conditions must be an associative array.");
        }

        if ($self::$isActive != 3) {
            if ($self::$isActive == 0) {
                $where['active'] = 0;
            } else {
                $where['active'] = 1;
            }
        }

        $select = "*";
        if (is_array($extra) && isset($extra['select'])) $select = $extra['select'];

        $tbl = $self->table;

        $useLegacy = true;
        foreach ($where as $k => $v) {
            if (is_array($v) || strtolower($k) === "or" || strtolower($k) === "and" || strtolower($k) === "like" || preg_match('/\s(=|!=|>|<|>=|<=)$/i', $k)) {
                $useLegacy = false;
                break;
            }
        }

        if ($useLegacy) {
            $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :$col", array_keys($where)));
            $bindings = [];
            foreach ($where as $col => $val) {
                $bindings[":$col"] = $val;
            }
        } else {
            [$whereClause, $bindings] = $self->buildWhere($where);
        }

        $sql = "SELECT {$select} FROM `{$tbl}`" . ($whereClause ? " WHERE $whereClause" : "");

        $limit = null;
        $offset = null;
        $page = 1;

        if (is_numeric($extra)) $limit = (int)$extra;
        elseif (is_array($extra)) {
            if (isset($extra['limit'])) {
                $limit = (int)$extra['limit'];
                if (isset($extra['page'])) {
                    $page = max(1, (int)$extra['page']);
                    $offset = ($page - 1) * $limit;
                }
            }
            if (isset($extra['group by'])) $sql .= " GROUP BY " . $extra['group by'];
            if (isset($extra['having'])) $sql .= " HAVING " . $extra['having'];
            if (isset($extra['order by'])) $sql .= " ORDER BY " . $extra['order by'];
        }

        if ($limit !== null) {
            $limit = (int)$limit;
            $offset = (int)($offset ?? 0);
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        self::$lastQuery = $sql;
        self::$lastBindings = $bindings;

        $stmt = $self->pdo->prepare($sql);
        foreach ($bindings as $key => $val) {
            $stmt->bindValue($key, $val);
        }

        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $rc = $stmt->rowCount();
        self::$rowcount = $rc;
        $stmt->closeCursor();

        $countSql = "SELECT COUNT(*) as cnt FROM `{$tbl}`" . ($whereClause ? " WHERE $whereClause" : "");
        $countStmt = $self->pdo->prepare($countSql);
        $countStmt->execute($bindings);
        $total = (int)$countStmt->fetch(\PDO::FETCH_ASSOC)['cnt'];
        $countStmt->closeCursor();

        self::$totalRecords = $total;
        self::$totalPages = ($limit !== null && $limit > 0) ? (int)ceil($total / $limit) : 1;
        self::$currentPage = $page;

        return $rc > 0 ? array_map([$self, 'hydrate'], $rows) : [];
    }

    protected function buildWhere(array $where, string $glue = "AND", &$bindings = [], &$paramIndex = 0): array
    {
        $clauses = [];
        foreach ($where as $key => $val) {
            if (strtolower($key) === "or" || strtolower($key) === "and") {
                $subClauses = [];
                $subBindings = [];
                foreach ($val as $cond) {
                    [$clause, $bind] = $this->buildWhere($cond, strtoupper($key), $bindings, $paramIndex);
                    if ($clause) $subClauses[] = $clause;
                    $subBindings = array_merge($subBindings, $bind);
                }
                $clauses[] = "(" . implode(" " . strtoupper($key) . " ", $subClauses) . ")";
                $bindings = array_merge($bindings, $subBindings);
            } elseif (strtolower($key) === "like") {
                foreach ($val as $col => $v) {
                    $param = ":p" . (++$paramIndex);
                    $clauses[] = "`$col` LIKE $param";
                    $bindings[$param] = "%$v%";
                }
            } elseif (is_array($val) && isset($val['between']) && is_array($val['between']) && count($val['between']) === 2) {
                $param1 = ":p" . (++$paramIndex);
                $param2 = ":p" . (++$paramIndex);
                $clauses[] = "`$key` BETWEEN $param1 AND $param2";
                $bindings[$param1] = $val['between'][0];
                $bindings[$param2] = $val['between'][1];
            } elseif (is_array($val) && isset($val['not between']) && is_array($val['not between']) && count($val['not between']) === 2) {
                $param1 = ":p" . (++$paramIndex);
                $param2 = ":p" . (++$paramIndex);
                $clauses[] = "`$key` NOT BETWEEN $param1 AND $param2";
                $bindings[$param1] = $val['not between'][0];
                $bindings[$param2] = $val['not between'][1];
            } else {
                if (preg_match('/^([a-zA-Z0-9_]+)\s*(=|!=|>|<|>=|<=)$/', $key, $m)) {
                    $col = $m[1];
                    $op = $m[2];
                    $param = ":p" . (++$paramIndex);
                    $clauses[] = "`$col` $op $param";
                    $bindings[$param] = $val;
                } elseif (is_array($val)) {
                    $placeholders = [];
                    foreach ($val as $v) {
                        $param = ":p" . (++$paramIndex);
                        $placeholders[] = $param;
                        $bindings[$param] = $v;
                    }
                    $clauses[] = "`$key` IN (" . implode(",", $placeholders) . ")";
                } else {
                    $param = ":p" . (++$paramIndex);
                    $clauses[] = "`$key` = $param";
                    $bindings[$param] = $val;
                }
            }
        }
        return [implode(" $glue ", $clauses), $bindings];
    }


    public static function totalRows(): int
    {
        return self::$totalRecords ?? 0;
    }

    public static function select(string|array|null $columns = null, array $extra = [])
    {
        $self = static::instance();

        if ($columns === null || $columns === [] || $columns === '') $cols = '*';
        elseif (is_array($columns)) $cols = implode(',', array_map(fn($c) => "`" . trim($c, '`') . "`", $columns));
        else $cols = "`" . trim($columns, '`') . "`";

        $sql = "SELECT $cols FROM `{$self->table}`";
        if (isset($extra['group by'])) $sql .= " GROUP BY " . $extra['group by'];
        if (isset($extra['having'])) $sql .= " HAVING " . $extra['having'];
        if (isset($extra['order by'])) $sql .= " ORDER BY " . $extra['order by'];
        if (isset($extra['limit'])) $sql .= " LIMIT " . (int)$extra['limit'];
        if (isset($extra['offset'])) $sql .= " OFFSET " . (int)$extra['offset'];

        self::$lastQuery = $sql;
        self::$lastBindings = [];

        $stmt = $self->pdo->prepare($sql);
        $stmt->execute();
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        self::$rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        return self::$rowcount > 0 ? array_map([$self, 'hydrate'], $rows) : [];
    }

    public static function totalPages(): int
    {
        return self::$totalPages ?? 1;
    }

    public static function currentPage(): int
    {
        return self::$currentPage ?? 1;
    }

    public static function first(array $conditions = [])
    {
        $self = static::instance();
        if (empty($conditions)) {
            $sql = "SELECT * FROM `{$self->table}` LIMIT 1";
            self::$lastQuery = $sql;
            self::$lastBindings = [];
            $stmt = $self->pdo->prepare($sql);
            $stmt->execute();
        } else {
            $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :$col", array_keys($conditions)));
            $sql = "SELECT * FROM `{$self->table}` WHERE $whereClause LIMIT 1";
            self::$lastQuery = $sql;
            self::$lastBindings = $conditions;
            $stmt = $self->pdo->prepare($sql);
            $stmt->execute($conditions);
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        self::$rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        return $row ? $self->hydrate($row) : [];
    }

    public static function last(array $conditions = [])
    {
        $self = static::instance();
        if (empty($conditions)) {
            $sql = "SELECT * FROM `{$self->table}` ORDER BY id DESC LIMIT 1";
            self::$lastQuery = $sql;
            self::$lastBindings = [];
            $stmt = $self->pdo->prepare($sql);
            $stmt->execute();
        } else {
            $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :$col", array_keys($conditions)));
            $sql = "SELECT * FROM `{$self->table}` WHERE $whereClause ORDER BY id DESC LIMIT 1";
            self::$lastQuery = $sql;
            self::$lastBindings = $conditions;
            $stmt = $self->pdo->prepare($sql);
            $stmt->execute($conditions);
        }

        $row = $stmt->fetch(\PDO::FETCH_ASSOC);
        self::$rowcount = $stmt->rowCount();
        $stmt->closeCursor();

        return $row ? $self->hydrate($row) : [];
    }

    public static function create(array $data)
    {
        $self = static::instance();
        $data = $self->filterFillable($data);
        $ts = $self->timestamps;
        if ($ts) {
            $now = date('Y-m-d H:i:s');
            if (is_array($ts)) {
                $data[$ts['created'] ?? 'created_at'] = $now;
                $data[$ts['updated'] ?? 'updated_at'] = $now;
            } else if (is_bool($ts)) {
                $data['created_at'] = $now;
                $data['updated_at'] = $now;
            } else {
                throw new Exception("Base table timestamps should only boolean or array");
            }
        }

        $columns = array_map(fn($col) => "`$col`", array_keys($data));
        $placeholders = array_map(fn($col) => ":$col", array_keys($data));
        $sql = "INSERT INTO `{$self->table}` (" . implode(",", $columns) . ") VALUES (" . implode(",", $placeholders) . ")";
        self::$lastQuery = $sql;
        self::$lastBindings = $data;

        $stmt = $self->pdo->prepare($sql);
        $stmt->execute($data);
        self::$rowcount = 1;
        $stmt->closeCursor();

        $data['_id'] = $self->pdo->lastInsertId();
        return static::instance($data);
    }

    public static function insert(array $data)
    {
        return self::create($data);
    }

    public static function deactivate($where)
    {
        $data['active'] = 0;
        return self::update($where, $data);
    }

    public static function activate($where)
    {
        $data['active'] = 1;
        return self::update($where, $data);
    }

    public static function update(array $where, array $data)
    {
        $self = static::instance();
        $data = $self->filterFillable($data);
        $ts = $self->timestamps;
        if ($ts) {
            if (is_array($ts)) {
                $data[$ts['updated'] ?? 'updated_at'] = date('Y-m-d H:i:s');
            } else if (is_bool($ts)) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            } else {
                throw new Exception("Base table timestamps should only boolean or array");
            }
        }

        $setClause = implode(', ', array_map(fn($col) => "`$col` = :$col", array_keys($data)));
        $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :where_$col", array_keys($where)));

        $bindings = array_merge(
            $data,
            array_combine(
                array_map(fn($k) => "where_$k", array_keys($where)),
                array_values($where)
            )
        );

        $sql = "UPDATE `{$self->table}` SET $setClause WHERE $whereClause";
        self::$lastQuery = $sql;
        self::$lastBindings = $bindings;

        $stmt = $self->pdo->prepare($sql);
        $stmt->execute($bindings);
        $rwCount = $stmt->rowCount();
        self::$rowcount = $rwCount;
        $stmt->closeCursor();

        return $rwCount;
    }

    public static function delete(array $where)
    {
        $self = static::instance();
        $whereClause = implode(' AND ', array_map(fn($col) => "`$col` = :$col", array_keys($where)));
        $sql = "DELETE FROM `{$self->table}` WHERE $whereClause";
        self::$lastQuery = $sql;
        self::$lastBindings = $where;

        $stmt = $self->pdo->prepare($sql);
        $stmt->execute($where);
        $rwCount = $stmt->rowCount();
        self::$rowcount = $rwCount;
        $stmt->closeCursor();

        return $rwCount;
    }

    public static function toFilteredArray(array $data)
    {
        $self = static::instance();
        return self::filterHidden($data, $self->hidden);
    }

    public static function jsonEncode(array $data)
    {
        return json_encode(static::toFilteredArray($data));
    }

    public static function getLastQuery(bool $withBindings = false)
    {
        $self = static::instance();
        if (!$withBindings) return self::$lastQuery;

        $query = self::$lastQuery;
        foreach (self::$lastBindings as $key => $value) {
            $pattern = '/:' . preg_quote($key, '/') . '\b/';
            $replacement = is_numeric($value) ? $value : $self->pdo->quote($value);
            $query = preg_replace($pattern, $replacement, $query);
        }
        return $query;
    }

    public static function getLastClearQuery(bool $withBindings = false)
    {
        $self = static::instance();
        if (!$withBindings) return self::$lastQuery;
        $query = self::$lastQuery;
        uksort(self::$lastBindings, fn($a, $b) => strlen($b) - strlen($a));
        foreach (self::$lastBindings as $key => $value) {
            $pattern = '/' . preg_quote($key, '/') . '\b/';

            if (is_numeric($value)) {
                $replacement = $value;
            } elseif ($value === null) {
                $replacement = 'NULL';
            } else {
                $replacement = $self->pdo->quote($value);
            }

            $query = preg_replace($pattern, $replacement, $query);
        }

        return $query;
    }

    public static function rowCount()
    {
        $self = static::instance();
        return self::$rowcount ?? 0;
    }

    public function toArray()
    {
        $data = $this->attributes;
        unset($data['_id']);
        return array_diff_key($data, array_flip($this->hidden));
    }

    public function insertID()
    {
        return $this->attributes['_id'] ?? null;
    }

    public function _id()
    {
        return $this->insertID();
    }

    public function data(string|array|null $key = null)
    {
        $attributes = $this->toArray();
        if ($key === null) return $attributes;
        if (is_string($key)) return $attributes[$key] ?? null;
        if (is_array($key)) return array_intersect_key($attributes, array_flip($key));
        return [];
    }

    public function excepts(string|array|null $key = null)
    {
        $attributes = $this->toArray();
        if ($key === null) return $attributes;
        if (is_string($key)) {
            unset($attributes[$key]);
            return $attributes;
        }
        if (is_array($key)) return array_diff_key($attributes, array_flip($key));
        return $attributes;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
