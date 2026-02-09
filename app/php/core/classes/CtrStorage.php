<?php

namespace Classes;

use Exception;
use Classes\Request;
use Classes\Random;

class CtrStorage
{
    protected static $autochangename = true;
    protected static $uploads = [];
    protected static $fulluploads = [];

    public static function auto_changename(bool $changename)
    {
        self::$autochangename = $changename;
    }
    protected static function dirfile()
    {
        return realpath(__DIR__ . "/../../../../");
    }

    protected static function dir(){
        return self::dirfile();
    }
    protected static function storagepath($full = true)
    {
        if($full){
            return self::dirfile() . "\\" . self::relativepath();
        }
        return self::relativepath();
    }

    public static function storage_path($fullpath = true)
    {
        return self::storagepath($fullpath);
    }

    public static function path($filepath=""){
        if(is_null($filepath) || $filepath == ""){
            return str_replace("\\", "/", self::relativepath());
        }
        return str_replace("\\","/", self::relativepath().$filepath);
    }

    public static function fpath($filepath=""){
        if(is_null($filepath) || $filepath == ""){
            return str_replace("\\", "/", self::storagepath());
        }
        return  str_replace("\\", "/", self::relativepath().$filepath);
    }

    protected static function relativepath()
    {
        return "_frontend\\core\\partials\\system\\storage\\";
    }

    //Pag gamit $upload =  Storage::upload_file($file)
    // $path = $upload['path'];
    static function upload_file($file, string|null $path = null)
    {
        if (! $file) {
            throw new Exception("File not found.!");
        }
        $pathname = self::storagepath();
        if (! is_dir($pathname)) {
            throw new Exception("Storage is not yet enabled");
        }
        if ($path) {
            $path = str_replace("/", "\\", $path);
            $pathname = $pathname . $path . "\\";
        }
        if (!is_dir($pathname)) {
            @mkdir($pathname, 0777, true);
        }

        if (is_string($file)) {
            $file = Request::file($file);
        }
        return self::upd($file, $pathname, $path);
    }

    public static function last_uploaded(bool $refresh = true): array|null
    {
        $ret = self::$uploads;
        if ($refresh) {
            self::$uploads = [];
        }
        return $ret;
    }

    public static function last_uploaded_fp(bool $refresh = true): array|null
    {
        $ret = self::$fulluploads;
        if ($refresh) {
            self::$fulluploads = [];
        }
        return $ret;
    }

    public static function last_single_uploaded_fp(bool $refresh = true): array|null
    {
        $ret = self::$fulluploads[0] ?? null;
        if ($refresh) {
            self::$fulluploads = [];
        }
        return $ret;
    }

    public static function last_single_uploaded(bool $refresh = true): string|null
    {
        $ret = self::$uploads[0] ?? null;
        if ($refresh) {
            self::$uploads = [];
        }
        return $ret;
    }


    public static function delete_files(array|string|null $files)
    {
        if (is_null($files)) {
            return false;
        }
        if (is_string($files)) {
            if (str_contains($files, "partials/system/storage") || str_contains($files, "partials\\system\\storage")) {
                return unlink($files);
            } else {
                return unlink(self::fpath($files));
            }
        }
        if (is_array($files)) {
            foreach ($files as $f => $v) {
                $istrue = self::delete_files($v);
                if (! $istrue) {
                    return false;
                }
            }
            return true;
        }
    }

    public static function fetch_files(string $dir = null, string|array $type = "*"): array
    {
        /**
         * usage:
         * * -all
         * *.jpg - all jpg
         * ["*.jpg", "*.png"] - multiple
         */
        $basePath = is_null($dir) || $dir === ""
            ? self::storagepath()
            : self::storagepath() . trim($dir, "\\/");

        $patterns = is_array($type) ? $type : [$type];
        $fullpaths = [];

        foreach ($patterns as $pattern) {
            $fullpaths = array_merge($fullpaths, glob($basePath . DIRECTORY_SEPARATOR . $pattern));
        }
        $fullpaths = array_map(fn($f) => str_replace("\\", "/", $f), $fullpaths);

        $storage = str_replace("\\", "/", rtrim(self::storagepath(), "/"));

        $root = str_replace("\\", "/", rtrim(self::dirfile(), "/"));

        $baseRelative = str_replace("\\", "/", trim(self::relativepath(), "\\/")) . "/";

        $spaths = array_map(function ($file) use ($storage) {
            return ltrim(str_replace($storage, "", $file), "/");
        }, $fullpaths);

        $paths = array_map(function ($spaths) use ($baseRelative) {
            return $baseRelative . $spaths;
        }, $spaths);

        return [
            "files"    => array_map("basename", $fullpaths),
            "path"    => $spaths,
            "rpath"     => $paths,
            "fullpath" => $fullpaths
        ];
    }

    protected static function upd($file, $dir, $path)
    {
        $path = is_null($path) ? "" : $path . "\\";
        $files = $file;
        $uploadDir = $dir;
        $single = false;
        if (!is_array($files['name'])) {
            $single = true;
            foreach ($files as $k => $v) {
                $files[$k] = [$v];
            }
        }

        $pp = [];
        $ff = [];
        $fp = [];
        $pt = [];
        if (self::$autochangename) {
            foreach ($files['tmp_name'] as $key => $tmpName) {
                $fileName = basename($files['name'][$key]);
                $extension = pathinfo($fileName, PATHINFO_EXTENSION);
                $newfilename = Random::text(17);
                $targetFile = $uploadDir . $newfilename . "." . $extension;
                if (move_uploaded_file($tmpName, $targetFile)) {
                    $fp[] = $targetFile;
                    $ff[] = $newfilename . "." . $extension;
                    $pt[] = $path . $newfilename . "." . $extension;
                    $pp[] = self::relativepath() . $path . $newfilename . "." . $extension;
                } else {
                    throw new Exception("File not uploaded. (" . $fileName . ")");
                }
            }
            self::$uploads = $pt;
            self::$fulluploads = $fp;
            if ($single) {
                return [
                    "fullpath" => $fp[0] ?? $fp,
                    "file" => $ff[0] ?? $ff,
                    "files" => $ff,
                    "filename" => $ff[0] ?? $ff,
                    "rpath" => $pp[0] ?? $pp,
                    "path" => $pt[0] ?? $pt
                ];
            }
            return [
                "fullpath" => $fp,
                "file" => $ff,
                "files" => $ff,
                "filename" => $ff,
                "rpath" => $pp,
                "path" => $pt
            ];
        } else {
            foreach ($files['tmp_name'] as $key => $tmpName) {
                $fileName = basename($files['name'][$key]);
                $targetFile = $uploadDir . $fileName;

                if (move_uploaded_file($tmpName, $targetFile)) {
                    $fp[] = $targetFile;
                    $ff[] = $fileName;
                    $pp[] = self::relativepath() . $path . $fileName;
                    $pt[] =  $path . $fileName;
                } else {
                    throw new Exception("File not uploaded. (" . $fileName . ")");
                }
            }
            self::$uploads = $pt;
            self::$fulluploads = $fp;
            if ($single) {
                return [
                    "fullpath" => $fp[0] ?? $fp,
                    "file" => $ff[0] ?? $ff,
                    "files" => $ff,
                    "filename" => $ff[0] ?? $ff,
                    "rpath" => $pp[0] ?? $pp,
                    "path" => $pt[0] ?? $pt
                ];
            }
            return [
                "fullpath" => $fp,
                "file" => $ff,
                "files" => $ff,
                "filename" => $ff,
                "rpath" => $pp,
                "path" => $pt
            ];
        }
    }
}
