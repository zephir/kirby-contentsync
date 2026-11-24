<?php

namespace Zephir\Contentsync\Models;

use Kirby\Exception\Exception;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
use Zephir\Contentsync\Helpers\Http;
use Zephir\Contentsync\Helpers\Logger;

class File
{
    /**
     * @var string
     */
    public $id;

    /**
     * @var string
     */
    public $path;

    /**
     * @var string
     */
    public $kirbyRootName;

    /**
     * @var string
     */
    public $checksum;

    /**
     * @var string $kirbyRootName
     * @var string $absolutePath
     * @var string $checksum
     */
    public function __construct($kirbyRootName, $absolutePath, $checksum = null)
    {
        $path = str_replace(kirby()->root($kirbyRootName), '', $absolutePath);
        $this->id = sha1($kirbyRootName . $path);
        $this->path = $path;
        $this->kirbyRootName = $kirbyRootName;
        $this->checksum = $checksum;
    }

    /**
     * @return string
     */
    public function getAbsolutePath()
    {
        return A::join(
            [
                kirby()->root($this->kirbyRootName),
                $this->path
            ],
            ''
        );
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return F::filename($this->path);
    }

    /**
     * @return File
     */
    public function generateChecksum()
    {
        $checksum = hash_init('sha1');

        $chunkSize = 1024 * 1024;
        $buffer = '';
        $handle = fopen($this->getAbsolutePath(), 'rb');

        if ($handle === false) {
            throw new Exception('Error while reading file ' . $this->id . '.');
        }

        while (!feof($handle)) {
            $buffer = fread($handle, $chunkSize);
            hash_update($checksum, $buffer);
        }

        $this->checksum = hash_final($checksum);

        fclose($handle);

        return $this;
    }

    /**
     * @return void
     */
    public function delete()
    {
        F::remove($this->getAbsolutePath());
        // Delete empty directories
        $dirname = F::dirname($this->getAbsolutePath());
        while (Dir::isEmpty($dirname)) {
            Dir::remove($dirname);
            $dirname = F::dirname($dirname);
        }
    }

    /**
     * Downloads the file from the source server. The download goes to a
     * temporary file first, so a failed download leaves the local file as it was.
     *
     * @return void
     * @throws Exception
     */
    public function update()
    {
        $source = rtrim(option('zephir.contentsync.source'), '/');
        $absolutePath = $this->getAbsolutePath();
        $tempPath = $absolutePath . '.contentsync-download';

        // Check if dir exists and create if not
        Dir::make(F::dirname($absolutePath));

        Logger::verbose('File: ' . $absolutePath);
        Logger::verbose('File ID: ' . $this->id);

        $fileUrl = $source . '/contentsync/file/' . $this->id;
        Logger::verbose('Server URL: ' . $fileUrl);

        $handle = fopen($tempPath, 'wb');

        if ($handle === false) {
            throw new Exception('Could not write to ' . $tempPath . '.');
        }

        try {
            $response = Http::get($fileUrl, $handle);
        } catch (\Throwable $e) {
            fclose($handle);
            F::remove($tempPath);
            throw $e;
        }

        fclose($handle);

        if ($response['status'] !== 200) {
            $errorMessage = Http::errorMessage((string)F::read($tempPath));
            F::remove($tempPath);

            throw new Exception('Server Error (' . $response['status'] . '): ' . $errorMessage . '\n' . ' Tried to connect to: ' . $fileUrl);
        }

        if (!rename($tempPath, $absolutePath)) {
            F::remove($tempPath);
            throw new Exception('Could not write to ' . $absolutePath . '.');
        }
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

}