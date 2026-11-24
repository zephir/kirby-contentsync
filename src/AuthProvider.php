<?php

namespace Zephir\Contentsync;

use Kirby\Exception\Exception;
use Kirby\Exception\PermissionException;

class AuthProvider
{

    /**
     * Checks the bearer token of the current request.
     *
     * @throws Exception
     * @throws PermissionException
     */
    public static function validate()
    {
        $validToken = (string)option('zephir.contentsync.token');

        if ($validToken === '') {
            throw new Exception('No token configured.');
        }

        // Kirby's request object matches header names case-insensitively
        // (HTTP/2 sends them in lowercase) and also finds the header when
        // Apache passes it on as REDIRECT_HTTP_AUTHORIZATION
        $header = kirby()->request()->header('Authorization');

        if (!is_string($header) || trim($header) === '') {
            throw new PermissionException('Authorization header missing.');
        }

        if (preg_match('/^Bearer\s+(.+)$/is', $header, $matches) !== 1) {
            throw new PermissionException('Invalid token.');
        }

        // A plain message works with every Kirby version: Kirby 6 no longer
        // accepts an options array, and PermissionException defaults to 403
        if (!hash_equals($validToken, $matches[1])) {
            throw new PermissionException('Invalid token.');
        }
    }

}
