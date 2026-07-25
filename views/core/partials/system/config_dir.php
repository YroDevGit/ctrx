<?php
if (! function_exists("in_directory")) {
    function in_directory(string|array $filter, $dir = null)
    {
        $dir = $dir ?? $_GET['dir'] ?? null;
        if (! $dir) {
            throw new Exception("Request directory not found.!");
        }

        if (is_string($filter)) {
            $filter = trim($filter, " /\\");
            if (str_starts_with($dir, $filter)) {
                return true;
            } else {
                return false;
            }
        }

        if (is_array($filter)) {
            foreach ($filter as $k => $v) {
                $v = trim($v, " /\\");
                if (str_starts_with($dir, $v)) {
                    return true;
                }
            }
            return false;
        }
        return false;
    }
}

if (! function_exists("include_all_subfolders")) {
    function all_subfolders(string|null $dir)
    {
        if (! $dir) {
            return $_GET['dir'] ?? null;
        }
        $dir = trim($dir, " /\\");
        return $dir . "/*";
    }
}