<?php

namespace Zephir\Contentsync;

use Kirby\Exception\Exception;
use Kirby\Exception\NotFoundException;
use Kirby\Http\Header;
use Kirby\Http\Response;
use Kirby\Filesystem\F;
use Zephir\Contentsync\Collections\Files;
use \Zephir\Contentsync\Models\File;

class FileProvider
{
    private const CHUNK_SIZE = 1024 * 1024;

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
     * @return void
     */
    public static function fileDownload(string $fileId)
    {
        $files = new Files();
        $file = $files->getFile($fileId);

        if ($file === null) {
            throw new NotFoundException('File ' . $fileId . ' not found.');
        }

        // Drop anything buffered so far, so only the file contents are sent.
        // There may be no buffer at all (output_buffering = 0), so don't
        // call ob_clean() unconditionally. Stop at a buffer that can't be
        // removed, or the loop would never end.
        while (ob_get_level() > 0) {
            if (!(ob_get_status()['flags'] & PHP_OUTPUT_HANDLER_REMOVABLE)) {
                break;
            }
            ob_end_clean();
        }

        self::returnFileStream($file);
    }

    /**
     * @param File $file
     */
    private static function returnFileStream($file)
    {
        $mime = F::extensionToMime(F::extension($file->getAbsolutePath())) ?? 'text/plain';
        Header::contentType($mime);

        $buffer = '';
        $handle = fopen($file->getAbsolutePath(), 'rb');

        if ($handle === false) {
            throw new Exception('Error while reading file ' . $file->id . '.');
        }

        while (!feof($handle)) {
            $buffer = fread($handle, self::CHUNK_SIZE);
            echo $buffer;
            flush();
        }

        fclose($handle);
    }

}