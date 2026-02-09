<?php

namespace Classes;

class Collection
{
    protected array $items = [];
    protected bool $singleRow = false;
    protected bool $wasMultiDim = false;

    private function __construct(array $items)
    {
        if (self::isAssoc($items)) {
            $this->items = [$items];
            $this->singleRow = true;
            $this->wasMultiDim = false;
        } else {
            $this->items = $items;
            $this->singleRow = false;
            $this->wasMultiDim = self::isMultiDim($items);
        }
    }

    public static function data(array|null|bool $items): self
    {
        if (is_bool($items)) {
            $items = [];
        }
        $items = is_null($items) ? [] : $items;
        return new self($items);
    }

    private static function isAssoc(array $arr): bool
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    private static function isMultiDim(array $arr): bool
    {
        foreach ($arr as $v) {
            if (is_array($v)) return true;
        }
        return false;
    }

    public function get(string|array $keys)
    {
        return $this->pick($keys);
    }

    public function extract(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            $this->items = [];
            return $this;
        }

        if (is_string($keys)) {
            $keys = [$keys];
        }

        $flat = [];
        foreach ($this->items as $item) {
            foreach ($keys as $key) {
                if (array_key_exists($key, $item)) {
                    $flat[] = $item[$key];
                }
            }
        }

        $this->items = $flat;
        $this->singleRow = false;
        $this->wasMultiDim = false;

        return $this;
    }

    public function pick(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            $this->items = [];
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            $filtered = [];
            foreach ($keys as $key) {
                if (array_key_exists($key, $item)) {
                    $filtered[$key] = $item[$key];
                }
            }
            return $filtered;
        }, $this->items);

        return $this;
    }

    public function except(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            $this->items = [];
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            return array_diff_key($item, array_flip($keys));
        }, $this->items);

        return $this;
    }

    public function take(array $conditions, string $option = "equal"): self
    {
        if ($this->trulyEmpty($this->items) || empty($conditions)) {
            return $this;
        }

        $this->items = array_values(array_filter($this->items, function ($item) use ($conditions, $option) {
            foreach ($conditions as $col => $val) {
                $itemVal = $item[$col] ?? '';
                if ($option === "equal" && (string)$itemVal !== (string)$val) return false;
                if ($option === "like" && stripos((string)$itemVal, (string)$val) === false) return false;
            }
            return true;
        }));

        return $this;
    }

    public function skip(array $conditions, string $option = "equal"): self
    {
        if ($this->trulyEmpty($this->items) || empty($conditions)) {
            return $this;
        }

        $this->items = array_values(array_filter($this->items, function ($item) use ($conditions, $option) {
            foreach ($conditions as $col => $val) {
                $itemVal = $item[$col] ?? '';
                if ($option === "equal" && (string)$itemVal === (string)$val) return false;
                if ($option === "like" && stripos((string)$itemVal, (string)$val) !== false) return false;
            }
            return true;
        }));

        return $this;
    }

    public function concat(array $definitions, string $separator = " ", bool $preserveColumns = false): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }

        $this->items = array_map(function ($item) use ($definitions, $separator, $preserveColumns) {
            $newItem = $preserveColumns ? $item : $item;
            foreach ($definitions as $alias => $cols) {
                $values = [];
                foreach ($cols as $col) {
                    $values[] = $item[$col] ?? '';
                }
                $newItem[$alias] = trim(implode($separator, array_filter($values, fn($v) => $v !== '')));
                if (!$preserveColumns) {
                    foreach ($cols as $col) {
                        unset($newItem[$col]);
                    }
                }
            }
            return $newItem;
        }, $this->items);

        return $this;
    }

    public function format(string $format, string|array $columns): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }

        if (is_string($columns)) {
            $columns = [$columns];
        }

        $this->items = array_map(function ($item) use ($format, $columns) {
            foreach ($columns as $col) {
                $val = $item[$col] ?? '';
                $item[$col . "1"] = str_replace("?", (string)$val, $format);
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function alter(array $map): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }

        $this->items = array_map(function ($item) use ($map) {
            foreach ($map as $old => $new) {
                if (array_key_exists($old, $item)) {
                    $item[$new] = $item[$old];
                    unset($item[$old]);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    static function string_format(string $string, string $separator, string $format, bool $asArray = false): string|array
    {
        $parts = explode($separator, $string);

        $formatted = array_map(function ($part) use ($format) {
            return str_replace("?", trim($part), $format);
        }, $parts);

        return $asArray ? $formatted : implode("", $formatted);
    }


    public function like(...$conditions): self
    {
        if ($this->trulyEmpty($this->items)) {
            $this->items = [];
            return $this;
        }

        $this->items = array_values(array_filter($this->items, function ($item) use ($conditions) {
            foreach ($conditions as $cond) {
                if (is_string($cond)) continue;
                if (self::isAssoc($cond)) {
                    foreach ($cond as $col => $val) {
                        $vals = is_array($val) ? $val : [$val];
                        foreach ($vals as $v) {
                            if (stripos((string)($item[$col] ?? ''), (string)$v) === false) return false;
                        }
                    }
                } elseif (is_array($cond)) {
                    $orPass = false;
                    foreach ($cond as $c) {
                        foreach ($c as $col => $val) {
                            if (stripos((string)($item[$col] ?? ''), (string)$val) !== false) {
                                $orPass = true;
                                break;
                            }
                        }
                    }
                    if (!$orPass) return false;
                }
            }
            return true;
        }));

        return $this;
    }

    public function equal(...$conditions): self
    {
        if ($this->trulyEmpty($this->items)) {
            $this->items = [];
            return $this;
        }

        $this->items = array_values(array_filter($this->items, function ($item) use ($conditions) {
            foreach ($conditions as $cond) {
                if (is_string($cond)) continue;
                if (self::isAssoc($cond)) {
                    foreach ($cond as $col => $val) {
                        $vals = is_array($val) ? $val : [$val];
                        foreach ($vals as $v) {
                            if ((string)($item[$col] ?? '') !== (string)$v) return false;
                        }
                    }
                } elseif (is_array($cond)) {
                    $orPass = false;
                    foreach ($cond as $c) {
                        foreach ($c as $col => $val) {
                            if ((string)($item[$col] ?? '') === (string)$val) {
                                $orPass = true;
                                break;
                            }
                        }
                    }
                    if (!$orPass) return false;
                }
            }
            return true;
        }));

        return $this;
    }

    public function limit(int $size, bool $reverse = false): self
    {
        if ($this->trulyEmpty($this->items) || $size <= 0) {
            $this->items = [];
            $this->singleRow = false;
            return $this;
        }

        $this->items = $reverse ? array_slice($this->items, -$size, $size) : array_slice($this->items, 0, $size);
        return $this;
    }

    public function sort(string|array $columns = "firstname"): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }

        $sortColumns = [];
        if (is_string($columns)) {
            $sortColumns[$columns] = "asc";
        } elseif (is_array($columns)) {
            foreach ($columns as $col => $dir) {
                if (is_int($col)) $sortColumns[$dir] = "asc";
                else $sortColumns[$col] = strtolower($dir) === "desc" ? "desc" : "asc";
            }
        }

        usort($this->items, function ($a, $b) use ($sortColumns) {
            foreach ($sortColumns as $col => $dir) {
                $valA = $a[$col] ?? null;
                $valB = $b[$col] ?? null;
                $cmp = (is_numeric($valA) && is_numeric($valB)) ? ($valA <=> $valB) : strcasecmp((string)$valA, (string)$valB);
                if ($cmp !== 0) return $dir === "desc" ? -$cmp : $cmp;
            }
            return 0;
        });

        return $this;
    }

    public function strtolower(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = strtolower((string)$item[$key]);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function strtoupper(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = strtoupper((string)$item[$key]);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function ucfirst(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = ucfirst((string)$item[$key]);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function ucwords(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = ucwords((string)$item[$key]);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function apply(string|array $keys, callable $callback): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys, $callback) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = $callback($item[$key], $key, $item);
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function encrypt(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = encrypt(strval($item[$key]));
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function decrypt(string|array $keys): self
    {
        if ($this->trulyEmpty($this->items)) {
            return $this;
        }
        if (is_string($keys)) {
            $keys = [$keys];
        }

        $this->items = array_map(function ($item) use ($keys) {
            foreach ($keys as $key) {
                if (isset($item[$key])) {
                    $item[$key] = decrypt(strval($item[$key]));
                }
            }
            return $item;
        }, $this->items);

        return $this;
    }

    public function all(): array|null
    {
        if ($this->trulyEmpty($this->items)) return [];
        return $this->singleRow && !$this->wasMultiDim ? $this->items[0] : $this->items;
    }

    public function pack(): array|null
    {
        return $this->all();
    }

    public function X()
    {
        return $this->all();
    }

    public function exec()
    {
        return $this->all();
    }

    private static function trulyEmpty(array $arr): bool
    {
        if (empty($arr)) return true;
        if (count($arr) === 1 && is_array($arr[0]) && empty($arr[0])) return true;
        return false;
    }
}
