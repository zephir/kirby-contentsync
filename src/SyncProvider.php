<?php

namespace Zephir\Contentsync;

use GuzzleHttp\Client;
use Kirby\CLI\CLI;
use Kirby\Exception\Exception;
use Zephir\Contentsync\Collections\Files;
use Zephir\Contentsync\Models\File;

class SyncProvider
{
    /**
     * @var CLI $cli
     */
    private $cli;

    private $debug;

    /**
     * @param CLI $cli
     */
    public function __construct(CLI $cli, $debug = false)
    {
        $this->cli = $cli;
        $this->debug = $debug;
    }

    public function sync()
    {
        $this->cli->out('Starting sync process.');

        try {
            $localFiles = new Files();
            $localFiles->collectFiles();

            $files = $this->fetchFiles();

            $fileActions = $localFiles->compare($files);

            $deleteCount = count($fileActions['delete']);
            $createCount = count($fileActions['create']);
            $updateCount = count($fileActions['update']);

            $this->cli->br();
            $this->cli->backgroundLightGray()->out($deleteCount . ' files to <red>delete</red>.');
            $this->cli->backgroundLightGray()->out($createCount . ' files to <green>create</green>.');
            $this->cli->backgroundLightGray()->out($updateCount . ' files to <blue>update</blue>.');
            $this->cli->br();

            if ($deleteCount || $createCount || $updateCount) {
                $this->cli->confirmToContinue('Do you want to continue?');
                $this->cli->clear();
            }

            // Remove deleted files
            if ($deleteCount) {
                $progress = $this->cli->progress()->total($deleteCount);
                foreach ($fileActions['delete'] as $file) {
                    $progress->advance(1, "Deleting {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->delete();
                }
                $progress->current($deleteCount, 'All files deleted.');
            }

            // Create new files
            if ($createCount) {
                $progress = $this->cli->progress()->total($createCount);
                foreach ($fileActions['create'] as $file) {
                    $progress->advance(1, "Creating {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->update();
                }
                $progress->current($createCount, 'All files created.');
            }

            // Update changed files
            if ($updateCount) {
                $progress = $this->cli->progress()->total($updateCount);
                foreach ($fileActions['update'] as $file) {
                    $progress->advance(1, "Updating {{$file->kirbyRootName}}/.../{$file->getFilename()}");
                    $file->update();
                }
                $progress->current($updateCount, 'All files updated.');
            }

            $this->cli->success("Everything is up to date.");
        } catch (\Exception $e) {
            $this->cli->error($e->getMessage());
        }
    }

    /**
     * @return Files
     */
    private function fetchFiles()
    {
        $this->cli->out("Fetching file list from " . option('zephir.contentsync.source'));

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, option('zephir.contentsync.source') . '/contentsync/files');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . option('zephir.contentsync.token')));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        $response = json_decode($response);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if ($response === NULL) {
            throw new Exception('Malformed JSON response from server.');
        }
        if ($httpcode !== 200) {
            throw new Exception([
                'fallback' => 'Server: ' . $response->message . ' in ' . $response->file . ' on line ' . $response->line,
                'httpCode' => $httpcode
            ]);
        }

        $files = new Files();
        return $files->setFiles($response);
    }

}