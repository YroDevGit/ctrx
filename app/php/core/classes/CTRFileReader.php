<?php

namespace Classes;

//requirements: composer require phpoffice/phpspreadsheet
use PhpOffice\PhpSpreadsheet\IOFactory;

use ZipArchive;

class CTRFileReader
{

    public static function read($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        return file_get_contents($path);
    }


    public static function readLines($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }
        return file($path, FILE_IGNORE_NEW_LINES);
    }


    public static function readJson($path)
    {
        $content = self::read($path);
        return json_decode($content, true);
    }

    public static function readCsv($path, $delimiter = ",")
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $rows = [];
        if (($handle = fopen($path, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, $delimiter)) !== false) {
                $rows[] = $data;
            }
            fclose($handle);
        }
        return $rows;
    }


    public static function readDocx($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $zip = new ZipArchive;
        if ($zip->open($path) === true) {
            $xml = $zip->getFromName("word/document.xml");
            $zip->close();

            if ($xml) {
                $content = strip_tags($xml);
                return preg_split("/\r\n|\r|\n/", trim($content));
            }
        }

        return [];
    }


    public static function readExcel($path)
    {
        if (!file_exists($path)) {
            throw new \Exception("File not found: {$path}");
        }

        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray();
    }


    public static function fromFile(array $file, string $type)
    {
        if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
            throw new \Exception("Invalid uploaded file.");
        }

        $path = $file['tmp_name'];

        switch (strtolower($type)) {
            case 'csv':
                return self::readCsv($path);
            case 'json':
                return self::readJson($path);
            case 'docx':
                return self::readDocx($path);
            case 'excel':
            case 'xlsx':
                return self::readExcel($path);
            case 'text':
            default:
                return self::read($path);
        }
    }
}
