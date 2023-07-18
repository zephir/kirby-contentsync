<?php

namespace Zephir\Contentsync\Collections;

use Kirby\Toolkit\A;
use Kirby\Filesystem\Dir;
use Zephir\Contentsync\Helpers\Roots;
use Zephir\Contentsync\Models\File;

class Files
{
    /**
     * @var array $all
     */
    public $all;

    /**
     * @param array $files
     * @return Files
     */
    public function setFiles(array $files)
    {
        $this->all = A::map($files, function ($file) {
            return new File($file->kirbyRootName, $file->path, $file->checksum);
        });

        return $this;
    }

    /**
     * @param bool $generateChecksum
     * @return Files
     */
    public function collectFiles(bool $generateChecksum = false)
    {
        $files = [];

        foreach (Roots::getEnabled() as $kirbyRootName => $rootPath) {
            if (is_dir($rootPath)) {
                $files = A::merge($files, $this->collectFilesForRoot($kirbyRootName, $rootPath, $generateChecksum));
            } else if (is_file($rootPath)) {
                $file = new File($kirbyRootName, $rootPath);
                if ($generateChecksum) {
                    $file->generateChecksum();
                }
                $files[] = $file;
            }
        }

        $this->all = $files;

        return $this;
    }

    /**
     * @param string $fileId
     * @return File|null
     */
    public function getFile(string $fileId)
    {
        if (empty($this->all)) {
            $this->collectFiles();
        }

        $key = array_search($fileId, array_column($this->all, 'id'));
        if ($key !== false) {
            return A::get($this->all, $key);
        }

        return null;
    }

    /**
     * @param string $kirbyRootName
     * @param string $rootPath
     * @param bool $generateChecksum
     * @param array $results
     * @return array
     */
    private function collectFilesForRoot(string $kirbyRootName, string $rootPath, bool $generateChecksum = false, array &$results = [])
    {
        $files = Dir::read($rootPath, null, true);

        foreach ($files as $path) {
            if ($path && !is_dir($path)) {
                $file = new File($kirbyRootName, $path);
                if ($generateChecksum) {
                    $file->generateChecksum();
                }
                $results[] = $file;
            } else {
                $this->collectFilesForRoot($kirbyRootName, $path, $generateChecksum, $results);
            }
        }

        return $results;
    }

    /**
     * @param Files $files
     * @return array
     */
    public function compare(Files $files)
    {
        $actions = [
            'delete' => array_diff($this->all, $files->all),
            'create' => array_diff($files->all, $this->all),
            'update' => []
        ];

        foreach ($files->all as $file) {
            $localFile = $this->getFile($file->id);
            if ($localFile) {
                $localFile->generateChecksum();
            }
            if ($localFile && $file->checksum !== $localFile->checksum) {
                $actions['update'][] = $file;
            }
        }

        return $actions;
    }

}