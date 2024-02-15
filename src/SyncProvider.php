<?php

namespace Zephir\Contentsync;

use Curl\Curl;
use Kirby\CLI\CLI;
use Kirby\Exception\Exception;
use Zephir\Contentsync\Collections\Files;
use Zephir\Contentsync\Helpers\Logger;

class SyncProvider
{
    /**
     * @var CLI $cli
     */
    private $cli;

    /**
     * @param CLI $cli
     */
    public function __construct(CLI $cli)
    {
        $this->cli = $cli;
    }

    public function sync()
    {
        Logger::info('Starting sync process.');

        try {
            Logger::verbose('Collecting list of all local files.');
            $localFiles = new Files();
            $localFiles->collectFiles();

            Logger::verbose('Collecting list of all remote files.');
            $files = $this->fetchFiles();

            Logger::verbose('Comparing local and remote files.');
            $fileActions = $localFiles->compare($files);
            $deleteCount = count($fileActions['delete']);
            $createCount = count($fileActions['create']);
            $updateCount = count($fileActions['update']);

            Logger::br();
            Logger::getCli()->backgroundLightGray()->out($deleteCount . ' files to <red>delete</red>.');
            Logger::getCli()->backgroundLightGray()->out($createCount . ' files to <green>create</green>.');
            Logger::getCli()->backgroundLightGray()->out($updateCount . ' files to <blue>update</blue>.');
            Logger::br();

            if ($deleteCount || $createCount || $updateCount) {
                Logger::getCli()->confirmToContinue('Do you want to continue?');
            }

            // Remove deleted files
            if ($deleteCount) {
                $progress = Logger::getCli()->progress()->total($deleteCount);
                foreach ($fileActions['delete'] as $file) {
                    $progress->advance(1, "Deleting {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->delete();
                }
                $progress->current($deleteCount, 'All files deleted.');
            }
            Logger::verbose('All files to be deleted where deleted.');

            // Create new files
            if ($createCount) {
                $progress = Logger::getCli()->progress()->total($createCount);
                foreach ($fileActions['create'] as $file) {
                    $progress->advance(1, "Creating {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->update();
                }
                $progress->current($createCount, 'All files created.');
            }
            Logger::verbose('All files to be created where created.');

            // Update changed files
            if ($updateCount) {
                $progress = Logger::getCli()->progress()->total($updateCount);
                foreach ($fileActions['update'] as $file) {
                    $progress->advance(1, "Updating {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->update();
                }
                $progress->current($updateCount, 'All files updated.');
            }
            Logger::verbose('All files to be updated where updated.');

            Logger::success("Everything is up to date.");
        } catch (\Exception $e) {
            Logger::error($e->getMessage());
        }
    }

    /**
     * @return Files
     */
    private function fetchFiles()
    {
        $source = rtrim(option('zephir.contentsync.source'), '/');

        Logger::info("Fetching file list from " . $source);

        $curl = new Curl();
        $curl->setHeader('Authorization', 'Bearer ' . option('zephir.contentsync.token'));
        $curl->get($source . '/contentsync/files');

        if ($curl->error) {
            throw new Exception('Server Error (' . $curl->httpStatusCode . '): ' . $curl->errorMessage);
        }

        if (!is_array($curl->response)) {
            throw new Exception('Malformed JSON response from server. HTTP-Code: ' . $curl->httpStatusCode . '. Response:' . $curl->response);
        }

        if ($curl->httpStatusCode !== 200) {
            throw new Exception('Server Error (' . $curl->httpStatusCode .  '): ' . isset($curl->response->message) ? $curl->response->message : $curl->response);
        }

        $files = new Files();
        return $files->setFiles($curl->response);
    }

}