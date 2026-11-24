<?php

namespace Zephir\Contentsync;

use Kirby\Exception\Exception;
use Kirby\Exception\PermissionException;

class AuthProvider
{

    /**
     * @param string $token
     * @throws Exception
     * @throws PermissionException
     */
    public static function validate()
    {
        $validToken = option('zephir.contentsync.token');

        if (!$validToken) {
            throw new Exception('No token configured.');
        }

        $headers = getallheaders();

        if (!isset($headers['Authorization'])) {
            throw new PermissionException('Authorization header missing.');
        }

        $token = substr($headers['Authorization'], 7);

        if ($validToken !== $token) {
            // A plain message works with every Kirby version: Kirby 6 no longer
            // accepts an options array, and PermissionException defaults to 403
            throw new PermissionException('Invalid token.');
        }
    }

}