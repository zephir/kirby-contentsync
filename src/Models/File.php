<?php

namespace Zephir\Contentsync\Models;

use Kirby\Exception\Exception;
use Kirby\Filesystem\Dir;
use Kirby\Filesystem\F;
use Kirby\Toolkit\A;

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
        $path = str_replace(kirby()->roots()->{$kirbyRootName}, '', $absolutePath);
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
                kirby()->roots()->{$this->kirbyRootName},
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
        // Check if dir exists and create if not
        Dir::make(F::dirname($this->getAbsolutePath()));

        $fp = fopen($this->getAbsolutePath(), 'w');

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, option('zephir.contentsync.source') . '/contentsync/file/' . $this->id);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Authorization: Bearer ' . option('zephir.contentsync.token')));
        curl_setopt($ch, CURLOPT_FILE, $fp);

        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($httpcode !== 200) {
            $response = json_decode($response);
            throw new Exception([
                'fallback' => 'Server: ' . $response->message . ' in ' . $response->file . ' on line ' . $response->line,
                'httpCode' => $httpcode
            ]);
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