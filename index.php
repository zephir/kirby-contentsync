<?php

# Use @include_once in case plugin is not installed through zip
# see https://getkirby.com/docs/guide/plugins/plugin-setup-composer#support-for-plugin-installation-without-composer
@include_once __DIR__ . '/vendor/autoload.php';

use Kirby\Exception\Exception;
use Kirby\Http\Response;
use Zephir\Contentsync\AuthProvider;
use Zephir\Contentsync\FileProvider;
use Zephir\Contentsync\SyncProvider;

Kirby::plugin('zephir/contentsync', [
    'routes' => function ($kirby) {
        return [
            [
                'pattern' => 'contentsync/files',
                'method' => 'GET',
                'action' => function () {
                    try {
                        AuthProvider::validate();
                        return FileProvider::fileList();
                    } catch (Exception $e) {
                        return Response::json($e->toArray(), $e->getHttpCode());
                    }
                }
            ],
            [
                'pattern' => 'contentsync/file/(:any)',
                'method' => 'GET',
                'action' => function (string $fileId) {
                    try {
                        AuthProvider::validate();
                        FileProvider::fileDownload($fileId);
                        // Not really a nice solution, but we need to exit before
                        // kirby tries to set headers
                        exit;
                    } catch (Exception $e) {
                        return Response::json($e->toArray(), $e->getHttpCode());
                    }
                }
            ]
        ];
    },
    'commands' => [
        'content:sync' => [
            'description' => 'Sync content.',
            'args' => [
                'debug' => [
                    'prefix' => 'd',
                    'longPrefix' => 'debug',
                    'description' => 'Show debug informations.',
                    'defaultValue' => 0,
                    'castTo' => 'bool'
                ]
            ],
            'command' => function ($cli) {
                $syncProvider = new SyncProvider($cli, $cli->arg('debug'));
                $syncProvider->sync();
            }
        ]
    ],
    'options' => [
        'source' => null,
        'token' => 'abc',
        'enabledRoots' => [
            'content' => true,
            'accounts' => true,
            'license' => true
        ]
    ]
]);