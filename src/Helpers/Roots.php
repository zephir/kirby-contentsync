<?php

namespace Zephir\Contentsync\Helpers;

class Roots
{

    /**
     * @return array
     */
    public static function getEnabled()
    {
        $enabledRoots = option('zephir.contentsync.enabledRoots');
        $roots = [];

        foreach (kirby()->roots()->toArray() as $rootName => $root) {
            if (isset($enabledRoots[$rootName]) && $enabledRoots[$rootName] === true) {
                $roots[$rootName] = $root;
            }
        }

        return $roots;
    }

}