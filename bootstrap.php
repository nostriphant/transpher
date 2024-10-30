<?php

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

function file_append_contents(string $filename, string $contents): int {
    $handle = fopen($filename, 'a');
    $written = fwrite($handle, $contents);
    fclose($handle);
    return $written !== false ? $written : 0;
}
