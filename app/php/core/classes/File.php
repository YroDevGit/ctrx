<?php

namespace Classes;

use Classes\Request;
use Exception;

class File
{

    public static function encode_blob(array|null|bool $data, string|array $columns): array
    {
        if (is_bool($data)) {
            return [];
        }
        $data = is_null($data) ? [] : $data;
        $columns = is_array($columns) ? $columns : [$columns];

        $isSingle = array_keys($data) !== range(0, count($data) - 1);
        $rows = $isSingle ? [$data] : $data;

        foreach ($rows as &$row) {
            foreach ($columns as $column) {
                if (!isset($row[$column]) || !$row[$column]) {
                    continue;
                }
                if (is_null($row[$column]) || $row[$column] == "") {
                    continue;
                }

                $finfo = finfo_open(FILEINFO_MIME_TYPE);
                $mime  = finfo_buffer($finfo, $row[$column]);
                finfo_close($finfo);

                $row[$column] = "data:$mime;base64," . base64_encode($row[$column]);
            }
        }

        return $isSingle ? $rows[0] : $rows;
    }

    public static function blob_to_text(string|null|bool $blob): string|null
    {
        if (is_bool($blob)) {
            return null;
        }
        if (is_null($blob) || $blob === "") {
            return "";
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_buffer($finfo, $blob);
        finfo_close($finfo);

        return "data:$mime;base64," . base64_encode($blob);
    }

    public static function get($name, $type = null)
    {
        return Request::file($name, $type);
    }

    public static function file_extension($file, $type = "file")
    {
        $filename = null;
        $type = strtolower($type);
        if ($type == "file") {
            $filename = $file['name'];
        } else if ($type == "name") {
            $fl = $_FILES[$file];
            $filename = $fl['name'];
        } else {
            throw new Exception("Invalid file type");
        }

        return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    }

    public static function validate_extension($file, array $extensions)
    {
        if (! $file) {
            throw new Exception("File not found.!");
        }

        $extensions = array_map('strtolower', $extensions);
        $filename = $file;

        if (is_string($filename)) {
            $ext = self::file_extension($filename, "name");
            return in_array($ext, $extensions);
        } else if (is_array($filename)) {
            foreach ($filename as $value) {
                $ext = self::file_extension($value, "name");
                if (! in_array($ext, $extensions)) {
                    return false;
                }
            }
            return true;
        }

        return false;
    }

    protected static function getFileSize($file, string $sizetype = "mb")
    {
        $sizetype = strtolower($sizetype);
        $fileSizeBytes = $file['size'];

        return match ($sizetype) {
            "kb" => round($fileSizeBytes / 1024, 2),
            "mb" => round($fileSizeBytes / 1024 / 1024, 2),
            "gb" => round($fileSizeBytes / 1024 / 1024 / 1024, 2),
            default => throw new Exception("Invalid sizetype"),
        };
    }

    public static function size($file, string $sizetype = "mb")
    {
        if (! $file) {
            throw new Exception("File not found.!");
        }

        $size = $file['size'];
        if (! is_array($size)) {
            return self::getFileSize($file, $sizetype);
        } else {
            $sz = [];
            foreach ($size as $t => $v) {
                $fname = $file['name'][$t];
                $sz[$fname] = self::getFileSize(["size" => $v], $sizetype);
            }
            return $sz;
        }
    }

    public static function validate_size($file, float|int $maxsize, string $sizetype = "mb")
    {
        if (! $file) {
            throw new Exception("File not found.!");
        }

        $size = $file['size'];
        if (! is_array($size)) {
            $sz = self::getFileSize($file, $sizetype);
            return $sz <= $maxsize;
        } else {
            foreach ($size as $k => $v) {
                $sz = self::getFileSize(["size" => $v], $sizetype);
                if ($sz > $maxsize) {
                    return false;
                }
            }
            return true;
        }
    }

    public static function validate_mime_type($file, array $allowedMimes): bool
    {
        if (! $file) {
            throw new Exception("File not found.!");
        }

        $allowedMimes = array_map('strtolower', $allowedMimes);

        if (is_string($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = strtolower(finfo_file($finfo, $file['tmp_name']));
            finfo_close($finfo);

            return in_array($mime, $allowedMimes);
        }

        if (is_array($file['tmp_name'])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            foreach ($file['tmp_name'] as $tmp) {
                $mime = strtolower(finfo_file($finfo, $tmp));
                if (! in_array($mime, $allowedMimes)) {
                    finfo_close($finfo);
                    return false;
                }
            }
            finfo_close($finfo);
            return true;
        }

        return false;
    }


    public static function is_image($file)
    {
        if (! $file) {
            throw new Exception("File not found");
        }
        $tmp = $file['tmp_name'];

        if (is_string($tmp)) {
            if ($tmp && exif_imagetype($tmp)) {
                return true;
            } else {
                return false;
            }
        } else if (is_array($tmp)) {
            foreach ($tmp as $t => $v) {
                $isNot = self::is_image(["tmp_name" => $v]);
                if (! $isNot) {
                    return false;
                }
            }
            return true;
        } else {
            return false;
        }
    }
}
