<?php

use Kirby\Exception\Exception;
use Kirby\Http\Response;
use Zephir\Contentsync\AuthProvider;
use Zephir\Contentsync\FileProvider;
use Zephir\Contentsync\Helpers\Logger;
use Zephir\Contentsync\SyncProvider;

// Kirby's class loader, so the plugin also works when it was
// installed without Composer (download or git submodule)
load([
    'Zephir\\Contentsync\\AuthProvider' => 'src/AuthProvider.php',
    'Zephir\\Contentsync\\FileProvider' => 'src/FileProvider.php',
    'Zephir\\Contentsync\\SyncProvider' => 'src/SyncProvider.php',
    'Zephir\\Contentsync\\Collections\\Files' => 'src/Collections/Files.php',
    'Zephir\\Contentsync\\Helpers\\Http' => 'src/Helpers/Http.php',
    'Zephir\\Contentsync\\Helpers\\Logger' => 'src/Helpers/Logger.php',
    'Zephir\\Contentsync\\Helpers\\Roots' => 'src/Helpers/Roots.php',
    'Zephir\\Contentsync\\Models\\File' => 'src/Models/File.php',
], __DIR__);

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
                'verbose' => [
                    'prefix' => 'v',
                    'longPrefix' => 'verbose',
                    'description' => 'Verbose output.',
                    'noValue' => true
                ],
                'noninteractive' => [
                    'prefix' => 'n',
                    'longPrefix' => 'noninteractive',
                    'description' => 'Adopt changes automatically',
                    'noValue' => true
                ]
            ],
            'command' => function ($cli): void {
                Logger::setCli($cli);
                Logger::setLogLevel($cli->arg('verbose') ? 'verbose' : 'info');

                $syncProvider = new SyncProvider($cli);
                $syncProvider->setNoninteractiveMode($cli->arg('noninteractive'));
                $syncProvider->sync();
            }
        ]
    ],
    'options' => [
        'source' => null,
        'token' => null,
        'verifySSL' => true,
        'enabledRoots' => [
            'content' => true,
            'accounts' => true,
            'license' => true
        ]
    ]
]);
