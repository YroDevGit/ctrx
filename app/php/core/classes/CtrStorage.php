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

    private static $last_uploaded_files = [];

    public static function auto_changename(bool $changename)
    {
        self::$autochangename = $changename;
    }
    protected static function dirfile()
    {
        return realpath(__DIR__ . "/../../../../");
    }

    protected static function dir()
    {
        return self::dirfile();
    }
    protected static function storagepath($full = true)
    {
        if ($full) {
            return self::dirfile() . "\\" . self::relativepath();
        }
        return self::relativepath();
    }

    public static function create_storage() {}

    public static function storage_path($fullpath = true)
    {
        return self::storagepath($fullpath);
    }

    public static function path($filepath = "")
    {
        if (is_null($filepath) || $filepath == "") {
            return str_replace("\\", "/", self::relativepath());
        }
        return str_replace("\\", "/", self::relativepath() . $filepath);
    }

    public static function fpath($filepath = "")
    {
        if (is_null($filepath) || $filepath == "") {
            return str_replace("\\", "/", self::storagepath());
        }
        return  str_replace("\\", "/", self::relativepath() . $filepath);
    }

    protected static function relativepath()
    {
        return "views\\core\\partials\\storage\\";
    }

    public static function get_last_uploaded($single = true)
    {
        if ($single) {
            return self::$last_uploaded_files[0] ?? null;
        }
        return self::$last_uploaded_files;
    }

    //Pag gamit $upload =  Storage::upload_file($file)
    // $path = $upload['path'];
    static function upload_file($file, bool|string $storagePath = true, string|null $path = "public")
    {
        if (! $file) {
            return null;
        }
        if (is_string(($storagePath))) {
            $path = $storagePath;
        }
        $pathname = self::storagepath();
        if (! is_dir($pathname)) {
            mkdir($pathname);
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

        if ($storagePath) {
            $data = self::upd($file, $pathname, $path);
            self::$last_uploaded_files[] = [
                "filename" => $data["filename"] ?? null,
                "file" => $data["storage"] ?? null
            ];
            if (is_string($storagePath)) {
                $storagePath = trim($storagePath, "/");
                $storagePath = trim($storagePath, "\\");
                return isset($data['file']) ? "/ctrstorage/" . $storagePath . "/" . $data['file'] : null;
            }
            return isset($data['file']) ? "/ctrstorage/" . $data['file'] : null;
        }
        $data = self::upd($file, $pathname, $path);
        self::$last_uploaded_files[] = [
            "filename" => $data["filename"] ?? null,
            "file" => $data["storage"] ?? null
        ];
        return $data;
    }

    public static function rollback()
    {
        $lastUploaded = self::get_last_uploaded();
        if ($lastUploaded) {
            foreach ($lastUploaded as $k => $v) {
                $file = $v["file"] ?? null;
                if (! $file) continue;

                if (\Classes\Ctrx::file_exists_strict($file)) {
                    unlink($file);
                }
            }
        }
    }

    public static function ctrUploadImage($dir = "public", $roles = null)
    {
        if (is_array($roles)) {
            if (!\Classes\Ctrx::has_user_roles(...$roles)) {
                echo json_encode(['success' => false, 'message' => "User doesn't have an access to upload image."]);
                exit;
            }
        }
        $path = "views/core/partials/storage/$dir";
        $fullPath = $path;

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'No image uploaded or upload error']);
            exit;
        }

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $file = $_FILES['image'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imageTypes = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'];

        if (!in_array($extension, $imageTypes)) {
            echo json_encode(['success' => false, 'message' => 'Invalid image type']);
            exit;
        }

        $filename = time() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
        $filePath = $fullPath . DIRECTORY_SEPARATOR . $filename;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $relativePath = str_replace(dirname(__DIR__) . '/', '', $filePath);
            $relativePath = str_replace('\\', '/', $relativePath);

            $imageData = [
                'name' => $filename,
                'path' => $relativePath,
                'url' => "ctrstorage/$dir/" . $filename,
                'size' => $file['size'],
                'modified' => time(),
                'extension' => $extension,
                'type' => 'image'
            ];

            echo json_encode([
                'success' => true,
                'image' => $imageData,
                'message' => 'Image uploaded successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to move uploaded image'
            ]);
        }
        exit;
    }

    public static function ctrImages($dir = "public")
    {
        $publicFolder = $dir;
        $path =  "views/core/partials/storage/$publicFolder/";
        $fullPath = $path;

        if (!is_dir($fullPath)) {
            echo json_encode(['images' => []]);
            exit;
        }

        $images = [];
        $imageTypes = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'];

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $extension = strtolower($file->getExtension());
                if (in_array($extension, $imageTypes)) {
                    $relativePath = str_replace(dirname(__DIR__) . '/', '', $file->getPathname());
                    $relativePath = str_replace('\\', '/', $relativePath);

                    $images[] = [
                        'name' => $file->getFilename(),
                        'path' => $relativePath,
                        'url' => "/ctrstorage/$publicFolder/" . $file->getFilename(),
                        'size' => $file->getSize(),
                        'modified' => $file->getMTime(),
                        'extension' => $extension,
                        'type' => 'image'
                    ];
                }
            }
        }

        usort($images, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        return ['images' => array_values($images)];
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

    public static function last_single_uploaded_fp(bool $refresh = true): array|null|string
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
            if (str_contains($files, "views/core/partials/storage") || str_contains($files, "views\\core\\partials\\storage")) {
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
                    "path" => $pt[0] ?? $pt,
                    "storage" => $pp[0] ?? $pp
                ];
            }
            return [
                "fullpath" => $fp,
                "file" => $ff,
                "files" => $ff,
                "filename" => $ff,
                "rpath" => $pp,
                "path" => $pt,
                "storage" => $pp
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
                    "path" => $pt[0] ?? $pt,
                    "storage" => $pp[0] ?? $pp,
                ];
            }
            return [
                "fullpath" => $fp,
                "file" => $ff,
                "files" => $ff,
                "filename" => $ff,
                "rpath" => $pp,
                "path" => $pt,
                "storage" => $pp
            ];
        }
    }
}