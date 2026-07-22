<?php
namespace Classes;

class CtrxLogger
{
    /*
    |--------------------------------------------------------------------------
    | Log
    |--------------------------------------------------------------------------
    */

    /**
     * Create a new log entry.
     */
    public static function log(
        string $message,
        array $data = []
    ): int {

        return SQLite::insert('logs', [

            'request' => $data['request']
                ?? ($_SERVER['REQUEST_METHOD'] ?? ''),

            'message' => $message,

            'path' => $data['path'] ?? '',

            'endpoint' => $data['endpoint']
                ?? '',

            'server' => $data['server']
                ?? ($_SERVER['SERVER_NAME'] ?? ''),

            'ip' => $data['ip']
                ?? ($_SERVER['REMOTE_ADDR'] ?? ''),

            'device' => $data['device']
                ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''),

            'user' => $data['user']
                ?? '',

            'env' => $data['env'] ?? env('environment') ?? '',

            'route' => $data['route'] ?? ($_SERVER['REQUEST_URI'] ?? ''),

            'created_at' => $data['created_at']
                ?? date('Y-m-d H:i:s')
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | Get Logs
    |--------------------------------------------------------------------------
    */

    /**
     * Get all logs.
     */
    public static function all(
        ?int $limit = null,
        int $offset = 0
    ): array {

        $sql = "
            SELECT *
            FROM logs
            ORDER BY id DESC
        ";

        if ($limit !== null) {
            $sql .= "
                LIMIT :limit
                OFFSET :offset
            ";

            return SQLite::get(
                $sql,
                [
                    'limit' => $limit,
                    'offset' => $offset
                ]
            );
        }

        return SQLite::get($sql);
    }


    /**
     * Get one log by ID.
     */
    public static function find(
        int $id
    ): array|false {

        return SQLite::first(
            "
            SELECT *
            FROM logs
            WHERE id = ?
            ",
            [$id]
        );
    }


    /**
     * Get latest logs.
     */
    public static function latest(
        int $limit = 10
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            ORDER BY id DESC
            LIMIT :limit
            ",
            [
                'limit' => $limit
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Search
    |--------------------------------------------------------------------------
    */

    /**
     * Search logs by message.
     */
    public static function search(
        string $keyword
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE message LIKE ?
            ORDER BY id DESC
            ",
            [
                '%' . $keyword . '%'
            ]
        );
    }


    /**
     * Search across multiple log fields.
     */
    public static function searchAll(
        string $keyword
    ): array {

        $keyword = '%' . $keyword . '%';

        return SQLite::get(
            "
            SELECT *
            FROM logs

            WHERE message LIKE ?
            OR request LIKE ?
            OR path LIKE ?
            OR endpoint LIKE ?
            OR server LIKE ?
            OR ip LIKE ?
            OR device LIKE ?
            OR env LIKE ?
            OR route LIKE ?
            OR user LIKE ?

            ORDER BY id DESC
            ",
            [
                $keyword,
                $keyword,
                $keyword,
                $keyword,
                $keyword,
                $keyword,
                $keyword,
                $keyword
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Filter
    |--------------------------------------------------------------------------
    */

    /**
     * Get logs by HTTP request method.
     *
     * Example:
     * Logger::request('GET');
     */
    public static function request(
        string $request
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE request = ?
            ORDER BY id DESC
            ",
            [$request]
        );
    }


    /**
     * Get logs by IP address.
     */
    public static function ip(
        string $ip
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE ip = ?
            ORDER BY id DESC
            ",
            [$ip]
        );
    }


    /**
     * Get logs by user.
     */
    public static function user(
        string $user
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE user = ?
            ORDER BY id DESC
            ",
            [$user]
        );
    }


    /**
     * Get logs by endpoint.
     */
    public static function endpoint(
        string $endpoint
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE endpoint = ?
            ORDER BY id DESC
            ",
            [$endpoint]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Count
    |--------------------------------------------------------------------------
    */

    /**
     * Count all logs.
     */
    public static function count(): int
    {
        return SQLite::count('logs');
    }


    /**
     * Count logs matching a message.
     */
    public static function countSearch(
        string $keyword
    ): int {

        return (int) SQLite::value(
            "
            SELECT COUNT(*)
            FROM logs
            WHERE message LIKE ?
            ",
            [
                '%' . $keyword . '%'
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | Delete
    |--------------------------------------------------------------------------
    */

    /**
     * Delete one log by ID.
     */
    public static function delete(
        int $id
    ): int {

        return SQLite::delete(
            'logs',
            'id = :id',
            [
                'id' => $id
            ]
        );
    }


    /**
     * Delete multiple logs.
     */
    public static function deleteMany(
        array $ids
    ): int {

        if (empty($ids)) {
            return 0;
        }

        $placeholders = implode(
            ', ',
            array_fill(
                0,
                count($ids),
                '?'
            )
        );

        return SQLite::query(
            "
            DELETE FROM logs
            WHERE id IN ({$placeholders})
            ",
            $ids
        )->rowCount();
    }


    /**
     * Clear all logs.
     */
    public static function clear(): int
    {
        return SQLite::query(
            "DELETE FROM logs"
        )->rowCount();
    }


    /*
    |--------------------------------------------------------------------------
    | Maintenance
    |--------------------------------------------------------------------------
    */

    /**
     * Delete logs older than a specific number of days.
     *
     * Example:
     *
     * Logger::clearOlderThan(30);
     */
    public static function clearOlderThan(
        int $days
    ): int {

        return SQLite::query(
            "
            DELETE FROM logs
            WHERE created_at < datetime(
                'now',
                :days
            )
            ",
            [
                'days' => '-' . $days . ' days'
            ]
        )->rowCount();
    }


    /**
     * Delete logs before a specific date.
     *
     * Example:
     *
     * Logger::clearBefore('2026-07-01 00:00:00');
     */
    public static function clearBefore(
        string $date
    ): int {

        return SQLite::query(
            "
            DELETE FROM logs
            WHERE created_at < ?
            ",
            [$date]
        )->rowCount();
    }


    /*
    |--------------------------------------------------------------------------
    | Statistics
    |--------------------------------------------------------------------------
    */

    /**
     * Get the latest log.
     */
    public static function last(): array|false
    {
        return SQLite::first(
            "
            SELECT *
            FROM logs
            ORDER BY id DESC
            LIMIT 1
            "
        );
    }


    /**
     * Get the oldest log.
     */
    public static function firstLog(): array|false
    {
        return SQLite::first(
            "
            SELECT *
            FROM logs
            ORDER BY id ASC
            LIMIT 1
            "
        );
    }


    /**
     * Get logs created today.
     */
    public static function today(): array
    {
        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE date(created_at) = date('now')
            ORDER BY id DESC
            "
        );
    }


    /**
     * Get logs between two dates.
     */
    public static function between(
        string $start,
        string $end
    ): array {

        return SQLite::get(
            "
            SELECT *
            FROM logs
            WHERE created_at BETWEEN ? AND ?
            ORDER BY id DESC
            ",
            [
                $start,
                $end
            ]
        );
    }


    /**
     * Get the number of logs grouped by request method.
     */
    public static function requestStats(): array
    {
        return SQLite::get(
            "
            SELECT
                request,
                COUNT(*) AS total
            FROM logs
            GROUP BY request
            ORDER BY total DESC
            "
        );
    }


    /**
     * Get the number of logs grouped by IP.
     */
    public static function ipStats(): array
    {
        return SQLite::get(
            "
            SELECT
                ip,
                COUNT(*) AS total
            FROM logs
            GROUP BY ip
            ORDER BY total DESC
            "
        );
    }


    /**
     * Get the number of logs grouped by endpoint.
     */
    public static function endpointStats(): array
    {
        return SQLite::get(
            "
            SELECT
                endpoint,
                COUNT(*) AS total
            FROM logs
            GROUP BY endpoint
            ORDER BY total DESC
            "
        );
    }
}