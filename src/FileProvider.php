<?php

namespace Zephir\Contentsync;

use Kirby\Http\Response;
use Zephir\Contentsync\Collections\Files;

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

        return Response::file($file->getAbsolutePath());
    }

}