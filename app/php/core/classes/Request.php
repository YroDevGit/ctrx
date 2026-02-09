<?php

namespace Classes;

use Exception;
use TypeError;
use ValueError;
use Classes\Response;

class Request
{
    static function post(string $key, bool $trim = true)
    {
        $post = post($key);
        if (is_null($post)) {
            return null;
        }
        if (is_array($post)) {
            return $post;
        }
        if (is_string($post)) {
            return $trim ? trim($post) : $post;
        }
        return $post;
    }

    static function post_decrypt(string $key, $errormessage = null, bool $trim = true)
    {
        $post = self::post($key, $trim);
        if (! $post) {
            return null;
        }
        $post = decrypt($post);
        if (! $post) {
            Response::code(badrequest_code)->message($errormessage ?? "POST: '$key' value error.!")->send(badrequest_code);
        }
        return $post;
    }

    static function get_decrypt(string $key, $errormessage = null, bool $trim = true)
    {
        $get = self::get($key, $trim);
        if (! $get) {
            return null;
        }
        $get = decrypt($get);
        if (! $get) {
            Response::code(badrequest_code)->message($errormessage ?? "GET: '$key' value error.!")->send(badrequest_code);
        }
        return $get;
    }

    static function get_request_id()
    {
        return ctr_get_current_request_id();
    }

    static function array(string $key, string|null|int $subkey = null)
    {
        $post = post($key);
        if (! is_array($post)) {
            $type = gettype($post);
            throw new Exception("Request::array should be an array, given value is $type");
        }
        if ($subkey) {
            return $post[$subkey] ?? null;
        }
        return $post;
    }

    static function get(string $key, bool $trim = true)
    {
        $get = get($key);
        if (is_null($get)) {
            return null;
        }
        if ($trim) {
            return trim($get ?? "");
        }
        return $get;
    }

    static function all()
    {
        return postdata();
    }

    static function input(string $key, bool $trim = true)
    {
        return self::post($key, $trim);
    }

    static function headers(string|null $key = null, $ucwords = false)
    {
        if (is_null($key)) {
            return server_headers($key);
        } else {
            $key = strtolower($key);
            if ($ucwords) {
                return server_headers($key);
            }
            return server_headers($key);
        }
    }

    private static function fileGetter($name, $type = null, $st = 0)
    {
        try {
            $file = null;
            if (is_string($name)) {
                if (!isset($_FILES[$name]) || ! $_FILES[$name]) {
                    return null;
                }
                $file = $_FILES[$name];
            } else {
                $file = $name;
            }

            $nm = $file['name'] ?? null;

            if (!$nm) {
                return null;
            }

            if (is_array(($nm))) {
                $newarr = [];
                $ret = [];
                $count = 0;
                foreach ($nm as $n) {
                    $newarr['name'] = $file['name'][$count];
                    $newarr['error'] = $file['error'][$count];
                    $newarr['full_path'] = $file['full_path'][$count];
                    $newarr['size'] = $file['size'][$count];
                    $newarr['tmp_name'] = $file['tmp_name'][$count];
                    $newarr['type'] = $file['type'][$count];
                    $fer = self::file($newarr, $type, 1);
                    $ret[$count] = $fer;
                    $count++;
                }
                return $ret;
            }

            switch ($type) {
                case 'name':
                    return $file['name'];
                    break;

                case 'size':
                    return $file['size'];
                    break;

                case 'size_mb':
                    $fileSizeBytes = $file['size'];
                    $fileSizeMB = $fileSizeBytes / 1024 / 1024;
                    return round($fileSizeMB, 2);
                    break;

                case 'size_kb':
                    $fileSizeBytes = $file['size'];
                    $fileSizeMB = $fileSizeBytes / 1024;
                    return round($fileSizeMB, 2);
                    break;

                case 'size_gb':
                    $fileSizeBytes = $file['size'];
                    $fileSizeMB = $fileSizeBytes / 1024 / 1024 / 1024;
                    return round($fileSizeMB, 2);
                    break;

                case 'tmp_name':
                    return $file['tmp_name'];
                    break;

                case 'type':
                    return $file['type'];
                    break;

                case 'blob':
                    $data = file_get_contents($file['tmp_name']);
                    return $data;
                    break;

                case 'filetype':
                case 'extension':
                    $filename = $file['name'];
                    $extension = pathinfo($filename, PATHINFO_EXTENSION);
                    return $extension;
                    break;

                default:
                    return $file;
                    break;
            }
        } catch (TypeError $e) {
            return null;
        } catch (ValueError $e) {
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    public static function page()
    {
        return self::headers("ctr_pgntn_x_page");
    }

    public static function file($name, $type = null)
    {
        if (!isset($_FILES[$name]) || ! $_FILES[$name]) {
            return null;
        }
        $fl = $_FILES[$name]['name'];
        if (is_array($fl)) {
            throw new Exception("File $name should be a single file, given (Collections of file)");
        }
        return self::fileGetter($name, $type);
    }

    public static function files($name, $type = null)
    {
        if (!isset($_FILES[$name]) || ! $_FILES[$name]) {
            return null;
        }
        $fl = $_FILES[$name]['name'];
        if (! is_array($fl)) {
            throw new Exception("File $name should be a multiple file, given (single file)");
        }
        return self::fileGetter($name, $type);
    }

    public static function ql(string $type)
    {
        return self::post($type);
    }

    static function origin()
    {
        return $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    }
}
