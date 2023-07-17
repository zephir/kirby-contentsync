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
            throw new Exception('Authorization header missing.');
        }

        $token = substr($headers['Authorization'], 7);

        if ($validToken !== $token) {
            throw new Exception([
                'fallback' => 'Invalid token.',
                'httpCode' => 403,
                'translate' => false,
            ]);
        }
    }

}