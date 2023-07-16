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
                'method' => 'POST',
                'action' => function () {
                    try {
                        AuthProvider::validate($_POST['token']);
                        return FileProvider::fileList();
                    } catch (Exception $e) {
                        return Response::json($e->toArray(), $e->getHttpCode());
                    }
                }
            ],
            [
                'pattern' => 'contentsync/file/(:any)',
                'method' => 'POST',
                'action' => function (string $fileId) {
                    try {
                        AuthProvider::validate($_POST['token']);
                        return FileProvider::fileDownload($fileId);
                    } catch (Exception $e) {
                        var_dump($e);
                        return Response::json($e->toArray(), $e->getHttpCode());
                    }
                }
            ]
        ];
    },
    'commands' => [
        'content:sync' => [
            'description' => 'Sync all content.',
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
        'token' => null,
        'enabledRoots' => [
            'content' => true,
            'accounts' => true
        ]
    ]
]);