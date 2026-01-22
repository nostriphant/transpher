<?php
namespace {
    require_once __DIR__ . '/vendor/autoload.php';

    define('ROOT_DIR', __DIR__);
    is_dir(ROOT_DIR . '/logs') || mkdir(ROOT_DIR . '/logs');

    define('TRANSPHER_VERSION', file_get_contents(__DIR__ . '/VERSION'));

    $dotenv_file = ROOT_DIR . '/.env';
    is_file($dotenv_file) || touch($dotenv_file);
    $dotenv = Dotenv\Dotenv::createMutable(dirname($dotenv_file));
    $dotenv->load();

    use Monolog\Level;

    function translate_loglevel(string $loglevel): Level {
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

    return function (string $identifier, string $logfile_level): Psr\Log\LoggerInterface {
        $log = new Monolog\Logger($identifier);

        $log->pushHandler(new Monolog\Handler\StreamHandler(STDOUT, translate_loglevel($logfile_level)));

        Monolog\ErrorHandler::register($log);

        return $log;
    };
}