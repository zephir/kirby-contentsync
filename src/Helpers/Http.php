<?php

namespace Zephir\Contentsync\Helpers;

use Kirby\Exception\Exception;

/**
 * Minimal HTTP client for requests to the source server, built on
 * the curl extension so the plugin needs no Composer dependencies.
 */
class Http
{
    /**
     * Sends an authenticated GET request to the source server.
     * The body is returned, or written to the open stream $sink if given.
     *
     * @param string $url
     * @param resource|null $sink
     * @return array{status: int, body: string}
     * @throws Exception when no connection could be made
     */
    public static function get(string $url, $sink = null): array
    {
        $options = [
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . option('zephir.contentsync.token')],
            CURLOPT_CONNECTTIMEOUT => 30,
            // No total timeout: large files may take a while to download
            CURLOPT_TIMEOUT => 0,
        ];

        if ($sink !== null) {
            $options[CURLOPT_FILE] = $sink;
        } else {
            $options[CURLOPT_RETURNTRANSFER] = true;
        }

        if (option('zephir.contentsync.verifySSL') === false) {
            $options[CURLOPT_SSL_VERIFYPEER] = false;
            $options[CURLOPT_SSL_VERIFYHOST] = 0;
        }

        $curl = curl_init($url);
        curl_setopt_array($curl, $options);

        $body = curl_exec($curl);

        if ($body === false) {
            throw new Exception('Could not connect to ' . $url . ': ' . curl_error($curl));
        }

        return [
            'status' => (int)curl_getinfo($curl, CURLINFO_RESPONSE_CODE),
            'body' => is_string($body) ? $body : '',
        ];
    }

    /**
     * The error message of a JSON error response from the source server,
     * or the raw body if it isn't one.
     *
     * @param string $body
     * @return string
     */
    public static function errorMessage(string $body): string
    {
        $response = json_decode($body, true);

        if (is_array($response) && isset($response['message']) && is_string($response['message'])) {
            return $response['message'];
        }

        return trim($body) === '' ? 'Empty response' : $body;
    }
}
