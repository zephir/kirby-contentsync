<?php

namespace Zephir\Contentsync;

use Kirby\Http\Response;
use Kirby\Filesystem\F;
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

        return new Response(F::base64($file->getAbsolutePath()), 'text/plain');
    }

}