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
    private static $cache = [];

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

    public static function ctr_read_file($file_path, $mime_type)
    {
        read_ctr_file($file_path, $mime_type);
    }

    public static function ctr_remove_image($dir = null, $roles = null)
    {
        $dir = $dir ?? $_GET['dir'] ?? "public";
        if (is_array($roles)) {
            if (!\Classes\Ctrx::has_user_roles(...$roles) && ! empty($roles)) {
                echo json_encode(['success' => false, 'code' => unauthorized_code, 'message' => "User doesn't have an access to delete image."]);
                exit;
            }
        }
        $filename = $_GET['filename'] ?? null;
        if (! $filename) {
            echo json_encode(['success' => false, 'code' => 404, 'message' => "Filename not found.!"]);
            exit;
        }
        $filename = trim($filename, " /\\");
        $dir = trim($dir, " /\\");

        $path = "views/core/partials/storage/$dir/$filename";
        $fileExist = false;
        if (file_exists($path)) {
            $fileExist = true;
            unlink($path);
        }

        echo json_encode([
            'code' => 200,
            'success' => true,
            'path' => $path,
            'filename' => $filename,
            'storage' => "/ctrstorage/$dir/$filename",
            'message' => 'Image deleted successfully'
        ]);
        exit;
    }

    public static function ctr_upload_image($dir = null, $roles = null)
    {
        $dir = $dir ?? $_GET['dir'] ?? "public";
        if (! $roles) $role = null;
        if (is_array($roles)) {
            if (!\Classes\Ctrx::has_user_roles(...$roles) && ! empty($roles)) {
                echo json_encode(['success' => false, 'code' => unauthorized_code, 'message' => "User doesn't have an access to upload image."]);
                exit;
            }
        }
        $path = "views/core/partials/storage/$dir";
        $fullPath = $path;

        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'code' => 500, 'message' => 'No image uploaded or upload error']);
            exit;
        }

        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }

        $file = $_FILES['image'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $imageTypes = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'];

        if (!in_array($extension, $imageTypes)) {
            echo json_encode(['success' => false, 'code' => 500, 'message' => 'Invalid image type']);
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
                'url' => "/ctrstorage/$dir/" . $filename,
                'size' => $file['size'],
                'modified' => time(),
                'extension' => $extension,
                'type' => 'image'
            ];

            echo json_encode([
                'code' => 200,
                'success' => true,
                'image' => $imageData,
                'message' => 'Image uploaded successfully'
            ]);
        } else {
            echo json_encode([
                'code' => 500,
                'success' => false,
                'message' => 'Failed to move uploaded image'
            ]);
        }
        exit;
    }

    public static function get_images(...$dirs)
    {
        if (empty($dirs)) {
            $dirs = [$_GET['dir'] ?? "public"];
        }

        $dirs = is_array($dirs[0]) ? $dirs[0] : $dirs;

        $allImages = [];
        $imageTypes = ['png', 'jpg', 'jpeg', 'gif', 'bmp', 'webp', 'svg', 'ico'];

        foreach ($dirs as $dir) {
            $includeSubfolders = false;

            if (is_string($dir) && str_ends_with($dir, '/*')) {
                $dir = rtrim($dir, '/*');
                $includeSubfolders = true;
            }

            $publicFolder = $dir;
            $path = "views/core/partials/storage/$publicFolder/";
            $fullPath = $path;

            if (!is_dir($fullPath)) {
                continue;
            }

            if ($includeSubfolders) {
                $iterator = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($fullPath, \RecursiveDirectoryIterator::SKIP_DOTS),
                    \RecursiveIteratorIterator::SELF_FIRST
                );
            } else {
                $iterator = new \FilesystemIterator($fullPath, \FilesystemIterator::SKIP_DOTS);
            }

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $extension = strtolower($file->getExtension());
                    if (in_array($extension, $imageTypes)) {
                        $relativePath = str_replace(dirname(__DIR__) . '/', '', $file->getPathname());
                        $relativePath = str_replace('\\', '/', $relativePath);

                        $subPath = '';
                        $newDir = $publicFolder;
                        if ($includeSubfolders) {
                            $fullPathNormalized = rtrim(str_replace('\\', '/', $fullPath), '/');
                            $filePathNormalized = str_replace('\\', '/', $file->getPath());
                            $subPath = str_replace($fullPathNormalized, '', $filePathNormalized);
                            $subPath = ltrim($subPath, '/');
                            if ($subPath) {
                                $subPath .= '/';
                                $newDir = $publicFolder . ($subPath ? "/" . $subPath : "");
                                $newDir = trim($newDir, " /\\");
                            }
                        }

                        $allImages[] = [
                            'name' => $file->getFilename(),
                            'path' => $relativePath,
                            'url' => "/ctrstorage/$publicFolder/" . $subPath . $file->getFilename(),
                            'size' => $file->getSize(),
                            'modified' => $file->getMTime(),
                            'extension' => $extension,
                            'type' => 'image',
                            'source_dir' => $newDir,
                            'relativePath' => $newDir . "/" . $file->getFilename(),
                            'parentFolder' => $publicFolder,
                            'subfolder' => rtrim($subPath, '/') ?: null
                        ];
                    }
                }
            }
        }

        usort($allImages, function ($a, $b) {
            return $b['modified'] - $a['modified'];
        });

        echo json_encode(['images' => array_values($allImages)]);
        exit;
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

    public static function cleanPath(string $path)
    {
        $path = trim($path, "*");
        $path = trim($path, " /\\");
        $path = trim($path, "//");
        $path = trim($path, "\\\\");
        return $path;
    }

    /**
     * Main method: Get directory size with human-readable formatting
     * 
     * @param string $path Directory path
     * @param int $precision Number of decimal places (default: 2)
     * @return string|false Formatted size or false on error
     */
    public static function getSize($path, $precision = 2)
    {
        $path = self::cleanPath($path);
        $bytes = self::getSizeBytes($path);
        if ($bytes === false) {
            return false;
        }
        return self::formatBytes($bytes, $precision);
    }

    /**
     * Get directory size in bytes only
     * 
     * @param string $path Directory path
     * @return int|false Size in bytes or false on error
     */
    public static function getSizeBytes($path)
    {
        $path = self::cleanPath($path);
        if (!is_dir($path)) {
            return false;
        }

        $totalSize = 0;
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $totalSize += $file->getSize();
                }
            }
        } catch (Exception $e) {
            return false;
        }

        return $totalSize;
    }

    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Size in bytes
     * @param int $precision Number of decimal places (default: 2)
     * @return string Formatted size (e.g., "2.45 MB")
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        if (!is_numeric($bytes) || $bytes < 0) {
            return '0 B';
        }

        if ($bytes == 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB'];
        $i = floor(log($bytes, 1024));

        // Limit to available units
        $i = min($i, count($units) - 1);

        $size = $bytes / pow(1024, $i);
        return number_format($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Get directory size using system commands (Linux/Unix only) - Faster
     * 
     * @param string $path Directory path
     * @param int $precision Number of decimal places (default: 2)
     * @return string|false Formatted size or false on error
     */
    public static function getSizeFast($path, $precision = 2)
    {
        $path = self::cleanPath($path);
        if (!is_dir($path)) {
            return false;
        }

        $output = shell_exec("du -sb " . escapeshellarg($path) . " 2>/dev/null");
        if (!$output || !preg_match('/^(\d+)/', $output, $matches)) {
            return false;
        }

        $bytes = (int)$matches[1];
        return self::formatBytes($bytes, $precision);
    }

    /**
     * Get directory size with caching for multiple calls
     * 
     * @param string $path Directory path
     * @param int $precision Number of decimal places (default: 2)
     * @param int $cacheTime Cache lifetime in seconds (default: 300)
     * @return string|false Formatted size or false on error
     */
    public static function getSizeCached($path, $precision = 2, $cacheTime = 300)
    {
        $realPath = realpath($path);
        if ($realPath === false) {
            return false;
        }

        $cacheKey = md5($realPath);

        // Check cache
        if (isset(self::$cache[$cacheKey])) {
            list($size, $time) = self::$cache[$cacheKey];
            if (time() - $time < $cacheTime) {
                return $size;
            }
        }

        // Calculate size
        $size = self::getSize($path, $precision);

        // Store in cache
        self::$cache[$cacheKey] = [$size, time()];

        return $size;
    }

    /**
     * Check if directory size exceeds a limit
     * 
     * @param string $path Directory path
     * @param int $limit Size limit
     * @param string $unit Unit (B, KB, MB, GB, TB)
     * @return bool|array Returns array with details or false on error
     */
    public static function isExceeded($path, $limit, $unit = 'MB')
    {
        $bytes = self::getSizeBytes($path);
        if ($bytes === false) {
            return false;
        }

        $limitBytes = self::convertToBytes($limit, $unit);
        $exceeded = $bytes > $limitBytes;

        return [
            'exceeded' => $exceeded,
            'current_size' => self::formatBytes($bytes),
            'current_bytes' => $bytes,
            'limit' => $limit . ' ' . $unit,
            'limit_bytes' => $limitBytes,
            'difference' => self::formatBytes(abs($bytes - $limitBytes)),
            'percentage' => round(($bytes / $limitBytes) * 100, 2)
        ];
    }

    /**
     * Convert human-readable size to bytes
     * 
     * @param int $value Size value
     * @param string $unit Unit (B, KB, MB, GB, TB)
     * @return int|false Bytes or false on error
     */
    public static function convertToBytes($value, $unit = 'MB')
    {
        $units = [
            'B' => 1,
            'KB' => 1024,
            'MB' => 1048576,
            'GB' => 1073741824,
            'TB' => 1099511627776
        ];

        $unit = strtoupper($unit);
        if (!isset($units[$unit])) {
            return false;
        }

        return $value * $units[$unit];
    }

    /**
     * Get detailed information about a directory
     * 
     * @param string $path Directory path
     * @return array|false Array with details or false on error
     */
    public static function getInfo($path)
    {
        if (!is_dir($path)) {
            return false;
        }

        $bytes = self::getSizeBytes($path);
        if ($bytes === false) {
            return false;
        }

        // Count files and directories
        $fileCount = 0;
        $dirCount = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isFile()) {
                $fileCount++;
            } elseif ($item->isDir()) {
                $dirCount++;
            }
        }

        return [
            'path' => $path,
            'size_bytes' => $bytes,
            'size_formatted' => self::formatBytes($bytes),
            'file_count' => $fileCount,
            'directory_count' => $dirCount,
            'total_items' => $fileCount + $dirCount,
            'timestamp' => date('Y-m-d H:i:s'),
            'permissions' => substr(sprintf('%o', fileperms($path)), -4),
            'owner' => function_exists('posix_getpwuid') ? posix_getpwuid(fileowner($path))['name'] : fileowner($path)
        ];
    }

    /**
     * Get size of multiple directories
     * 
     * @param array $paths Array of directory paths
     * @param int $precision Number of decimal places (default: 2)
     * @return array Array of results
     */
    public static function getMultipleSizes($paths, $precision = 2)
    {
        $results = [];
        foreach ($paths as $path) {
            $size = self::getSize($path, $precision);
            $results[$path] = $size === false ? 'ERROR' : $size;
        }
        return $results;
    }

    /**
     * Get total size of multiple directories
     * 
     * @param array $paths Array of directory paths
     * @param int $precision Number of decimal places (default: 2)
     * @return string|false Total formatted size or false on error
     */
    public static function getTotalSize($paths, $precision = 2)
    {
        $totalBytes = 0;
        foreach ($paths as $path) {
            $bytes = self::getSizeBytes($path);
            if ($bytes === false) {
                return false;
            }
            $totalBytes += $bytes;
        }
        return self::formatBytes($totalBytes, $precision);
    }

    /**
     * Clear the cache
     */
    public static function clearCache()
    {
        self::$cache = [];
    }

    public static function getParentFolder(string $path){
        $path = self::cleanPath($path);
        $path = str_replace("\\", "/", $path);
        $exp = explode("/", $path);
        return $exp[0] ?? "";
    }

    public static function buildPath(...$folders){
        $filt = [];
        foreach($folders as $k => $v){
            $v = self::cleanPath($v);
            $filt[] = $v;
        }
        return implode("/", $filt);
    }
}