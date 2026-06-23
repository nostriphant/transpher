<?php


namespace nostriphant\Transpher;

use Monolog\Level;

class Logger {

    static function translate_loglevel(string $loglevel): Level {
        return match (strtoupper($loglevel)) {
            'DEBUG' => Level::Debug,
            'NOTICE' => Level::Notice,
            'INFO' => Level::Info,
            'WARNING' => Level::Warning,
            'ERROR' => Level::Error,
            'CRITICAL' => Level::Critical,
            'ALERT' => Level::Alert,
            'EMERGENCY' => Level::Emergency,
            default => Level::Info
        };
    }
    
    static function factory(string $identifier, string $logfile_level): \Psr\Log\LoggerInterface {
        $log = new \Monolog\Logger($identifier);
        $log->pushHandler(new \Monolog\Handler\StreamHandler(STDOUT, self::translate_loglevel($logfile_level)));
        \Monolog\ErrorHandler::register($log);
        return $log;
    }
}
