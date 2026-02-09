<?php
namespace Classes;


/**
 * CodeTazer ft. mongoDB
 * This feature needs setup as PHP has no built in tool for MongoDB.
 * 
 * Steps:
 * 1. Browse: https://pecl.php.net/package/mongodb/1.16.2/windows
 * 2. Download: MongoDD driver match to your PHP version
 * 3. To know your php version, CLI: php -v
 * 4. After downloaded, extract zip file.
 * 5. In the extracted file, copy php_mongodb.dll and add it to your php ext (E.g: C:\xampp\php\ext)
 * 6. Add mongodb in extensions: eg in php.ini: extension=mongodb
 * 7. save php.ini
 * 1. Open project cli and enter: composer require mongodb/mongodb
 * 8. ready...
 */


use Exception;
use MongoDB\Client;
use MongoDB\Driver\Session;

class CtrMongo
{
    private static $client;
    private static $db;

    private static $setclient = null;
    private static $setdb = null;
    private static $setup;
    private static ?Session $session = null;
    private static $inserID;

    private function __construct($set)
    {
        self::$setup = $set;
    }

    public static function setURI(string $uri)
    {
        self::$setclient = $uri;
    }

    public static function setDB(string $db)
    {
        self::$setdb = $db;
    }

    private static function init()
    {
        $uri = self::$setclient ?? getenv("MONGO_URI");
        $dbname = self::$setdb ?? getenv("MONGO_DB");

        if (! $uri) {
            throw new Exception("Mongo URI not set");
        }
        if (!$dbname) {
            throw new Exception("Mongo DB name not set");
        }

        self::$client = new Client($uri);
        self::$db = self::$client->$dbname;
    }

    public static function collection($name)
    {
        self::init();
        return new self(self::$db->$name);
    }

    public static function insert(array $data)
    {
        $result = self::$setup->insertOne($data);
        $id = $result->getInsertedId();
        self::$inserID = $id;
        $data["_id"] = (string)$id;
        return $data;
    }

    public static function _id()
    {
        return self::$inserID;
    }

    public static function delete(string|array $where)
    {
        if (is_string($where)) {
            $filter = ["_id" => new \MongoDB\BSON\ObjectId($where)];
            return self::$setup->deleteOne($filter)->getDeletedCount();
        } elseif (is_array($where)) {
            return self::$setup->deleteMany($where)->getDeletedCount();
        }
        throw new Exception("Invalid delete argument");
    }

    public static function update(array|string $where, array $updateData, array $options = [])
    {
        $filter = $where;
        $update = ['$set' => $updateData];

        if (is_string($filter)) {
            $filter = ["_id" => new \MongoDB\BSON\ObjectId($filter)];
            return self::$setup->updateOne($filter, $update, $options)->getModifiedCount();
        } elseif (is_array($filter)) {
            return self::$setup->updateMany($filter, $update, $options)->getModifiedCount();
        }
        throw new \Exception("Invalid update filter");
    }

    public static function find(array|string $where = [], array $options = [])
    {
        $filter = $where;
        if (is_string($filter)) {
            $filter = ["_id" => new \MongoDB\BSON\ObjectId($filter)];
        }

        $results = self::$setup->find($filter, $options)->toArray();

        $data =  array_map(function ($doc) {
            $doc["_id"] = (string)$doc["_id"];
            return $doc;
        }, $results);

        return $data ?? [];
    }

    public static function getAll($options)
    {
        return self::find([], $options) ?? [];
    }

    public static function findOne(array|string $where, array $options = [])
    {
        $data = self::find($where, $options);
        if (! $data) {
            return [];
        }
        return $data[0] ?? [];
    }

    public static function begin()
    {
        self::init();
        if (!self::$session) {
            self::$session = self::$client->startSession();
            self::$session->startTransaction();
        }
        return self::$session;
    }

    public static function commit()
    {
        if (self::$session) {
            self::$session->commitTransaction();
            self::$session->endSession();
            self::$session = null;
        }
    }

    public static function rollback()
    {
        if (self::$session) {
            self::$session->abortTransaction();
            self::$session->endSession();
            self::$session = null;
        }
    }

    public static function getSession()
    {
        return self::$session;
    }
}
