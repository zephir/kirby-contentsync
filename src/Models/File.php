<?php

namespace Zephir\Contentsync\Models;

use Curl\Curl;
use Kirby\Exception\Exception;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;
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
     * @param string $content
     * @return void
     */
    public function update()
    {
        $source = rtrim(option('zephir.contentsync.source'), '/');

        // Check if dir exists and create if not
        Dir::make(F::dirname($this->getAbsolutePath()));

        Logger::verbose('File: ' . $this->getAbsolutePath());
        Logger::verbose('File ID: ' . $this->id);

        $fileUrl = $source . '/contentsync/file/' . $this->id;
        Logger::verbose('Server URL: ' . $fileUrl);

        $curl = new Curl();
        $curl->setHeader('Authorization', 'Bearer ' . option('zephir.contentsync.token'));
        $curl->download(
            $fileUrl,
            $this->getAbsolutePath()
        );

        if ($curl->httpStatusCode !== 200) {
            $response = json_decode(F::read($this->getAbsolutePath()));
            $errorMessage = $curl->errorMessage;

            if (is_object($response)) {
                $errorMessage = $response->message;
            }

            F::remove($this->getAbsolutePath());
            Logger::verbose('Removing: ' . $this->getAbsolutePath());

            throw new Exception('Server: ' . $errorMessage . '\n' . ' Tried to connect to: ' . $source . '/contentsync/file/' . $this->id);
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