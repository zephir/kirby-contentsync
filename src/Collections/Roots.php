<?php

namespace Zephir\Contentsync\Collections;

class Roots
{

    /**
     * @var array
     */
    private static $allowedKirbyRoots = ['content', 'accounts'];

    /**
     * @return array
     */
    public static function getEnabled()
    {
        $enabledRoots = option('zephir.contentsync.enabledRoots');
        $roots = [];

        foreach (self::$allowedKirbyRoots as $rootName) {
            $root = kirby()->root($rootName);
            if (isset($enabledRoots[$rootName]) && $enabledRoots[$rootName] === true) {
                $roots[$rootName] = $root;
            }
        }

        return $roots;
    }

}