<?php

namespace App\Utilitaire;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

class Singleton_Logger extends Logger
{
    private static $_instance = null;

    public function __construct(string $name, array $handlers = [], array $processors = [], ?DateTimeZone $timezone = null)
    {
        parent::__construct($name, $handlers, $processors, $timezone );

    }

    public static function getInstance():Logger {

        if(is_null(self::$_instance)) {
            self::$_instance = new Singleton_Logger('cafe');
            self::$_instance->pushHandler(new StreamHandler(__DIR__.'/app.log', Logger::DEBUG));
        }

        return self::$_instance;
    }
}