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
    public static function validate(string $token)
    {
        $validToken = option('zephir.contentsync.token');

        if (!$validToken) {
            throw new Exception('No token configured.');
        }

        if (option('zephir.contentsync.token') !== $token) {
            throw new Exception([
                'fallback' => 'Invalid token.',
                'httpCode' => 403,
                'translate' => false,
            ]);
        }
    }

}