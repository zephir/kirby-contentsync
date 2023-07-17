<?php

namespace Zephir\Contentsync;

use Kirby\Exception\Exception;
use Kirby\Http\Header;
use Kirby\Http\Response;
use Kirby\Filesystem\F;
use Zephir\Contentsync\Collections\Files;
use \Zephir\Contentsync\Models\File;

define("CHUNK_SIZE", 1024 * 1024);

class FileProvider
{

    /**
     * @return Response
     */
    public static function fileList()
    {
        $files = new Files();

        return Response::json(
            $files
                ->collectFiles(true)
                ->all,
            200
        );
    }

    /**
     * @param string $fileId
     * @return Response
     */
    public static function fileDownload(string $fileId)
    {
        $files = new Files();
        $file = $files->getFile($fileId);

        ob_clean();
        self::returnFileStream($file);
        return new Response('');
    }

    /**
     * @param File $file
     */
    private static function returnFileStream($file)
    {
        Header::contentType(F::extensionToMime(F::extension($file->getAbsolutePath())));

        $buffer = '';
        $handle = fopen($file->getAbsolutePath(), 'rb');

        if ($handle === false) {
            throw new Exception('Error while reading file ' . $file->id . '.');
        }

        while (!feof($handle)) {
            $buffer = fread($handle, CHUNK_SIZE);
            echo $buffer;
            ob_flush();
            flush();
        }

        fclose($handle);
    }

}