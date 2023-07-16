<?php

namespace Zephir\Contentsync\Models;

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
        $this->checksum = sha1_file($this->getAbsolutePath());
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
    public function update($content)
    {
        Dir::make(F::dirname($this->getAbsolutePath()));
        F::write($this->getAbsolutePath(), $content);
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->id;
    }

}