<?php

namespace Zephir\Contentsync\Helpers;

use Kirby\CLI\CLI;
use League\CLImate\CLImate;

class Logger
{

    private static $cli;

    private static $logLevel = 'info';

    public static function setCli(CLI $cli)
    {
        self::$cli = $cli;
    }

    public static function setLogLevel($level)
    {
        self::$logLevel = $level;
    }

    public static function verbose($message)
    {
        if (self::$logLevel === 'verbose') {
            self::log($message, 'cyan');
        }
    }

    public static function info($message)
    {
        if (self::$logLevel  === 'verbose' || self::$logLevel === 'info') {
            self::log($message, 'cyan');
        }
    }

    public static function success($message)
    {
        self::log($message, 'green');
    }

    public static function error($message)
    {
        self::log($message, 'red');
    }

    public static function br() {
        self::$cli->br();
    }

    public static function getCli() {
        return self::$cli;
    }

    private static function log($message, $color = 'white')
    {
        $lines = explode("\\n", $message);
        foreach ($lines as $line) {
            $line = trim($line);
            self::$cli->{$color}()->out($line);
        }
    }

}