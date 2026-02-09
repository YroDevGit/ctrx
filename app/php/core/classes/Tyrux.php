<?php

namespace Classes;

/**
 * @author Tyrone malocon
 * @description: Tyrux REST for backend
 */
class Tyrux
{
    private static $baseUrl = '';
    private static $lastError = null;
    private static $statusCode;

    public static function setBaseUrl($url)
    {
        self::$baseUrl = rtrim($url, '/');
    }

    public static function request($method, $options)
    {
        $url = isset($options['url']) ? $options['url'] : '';
        $headers = isset($options['headers']) ? $options['headers'] : [];
        $data = isset($options['data']) ? $options['data'] : [];

        $ch = curl_init();
        $fullUrl = self::$baseUrl . $url;

        $curlOptions = [
            CURLOPT_URL => $fullUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => self::formatHeaders($headers),
        ];

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'])) {
            $curlOptions[CURLOPT_POSTFIELDS] = json_encode($data);
            $curlOptions[CURLOPT_HTTPHEADER][] = 'Content-Type: application/json';
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        self::$statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            self::$lastError = $error;
            return null;
        }

        return json_decode($response, true);
    }

    public static function lastError()
    {
        return self::$lastError;
    }

    public static function statusCode(){
        return self::$statusCode;
    }

    public static function get($options)
    {
        return self::request('GET', $options);
    }

    public static function post($options)
    {
        return self::request('POST', $options);
    }

    public static function put($options)
    {
        return self::request('PUT', $options);
    }

    public static function patch($options)
    {
        return self::request('PATCH', $options);
    }

    public static function delete($options)
    {
        return self::request('DELETE', $options);
    }

    private static function formatHeaders($headers)
    {
        $formatted = [];
        foreach ($headers as $key => $value) {
            $formatted[] = "$key: $value";
        }
        return $formatted;
    }
}
