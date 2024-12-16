<?php
namespace {
    require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);
is_dir(ROOT_DIR . '/logs') || mkdir(ROOT_DIR . '/logs');

$dotenv_file = ROOT_DIR . '/.env';
is_file($dotenv_file) || touch($dotenv_file);
$dotenv = Dotenv\Dotenv::createMutable(dirname($dotenv_file));
$dotenv->load();

    if (function_exists('array_find') === false) {

    /**
     * PHP 8.4 compat
     * @param array $array
     * @param callable $callback
     * @return mixed
     */
    function array_find(array $array, callable $callback): mixed {
        foreach ($array as $key => $value) {
            if ($callback($value, $key)) {
                return $value;
            }
        }

        return null;
    }

}

function iterator_map(\Traversable $iterator, callable $callback): \Traversable {
    foreach ($iterator as $key => $value) {
        yield $key => $callback($value);
    }
}

function file_append_contents(string $filename, string $contents): int {
    $handle = fopen($filename, 'a');
    $written = fwrite($handle, $contents);
    fclose($handle);
    return $written !== false ? $written : 0;
}

function in_range(string|int|float $value, string|int|float $start, string|int|float $end): bool {
    return in_array($value, range($start, $end));
}

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

return function (string $identifier, string $stdout_level, string $logfile_level): Psr\Log\LoggerInterface {
    $log = new Monolog\Logger($identifier);

    $log->pushHandler(new Monolog\Handler\StreamHandler(__DIR__ . '/logs/' . $identifier . '.log', translate_loglevel($logfile_level)));
    $log->pushHandler(new Monolog\Handler\StreamHandler(STDOUT, translate_loglevel($stdout_level)));

    Monolog\ErrorHandler::register($log);

    return $log;
};
}

namespace nostriphant\Transpher\Stores {

    function do_housekeeping(\nostriphant\Transpher\Relay\Store $store, array $whitelist_prototypes) {
        if (\nostriphant\Transpher\Nostr\Subscription::disabled($whitelist_prototypes)) {
            return;
        }

        return (match ($store::class) {
                    Disk::class => new Disk\Housekeeper($store),
            SQLite::class => new SQLite\Housekeeper($store),
            Memory::class => new Memory\Housekeeper($store),
            default => new NullHousekeeper()
        })();
    }

}