<?php

require_once __DIR__ . '/vendor/autoload.php';

assert(is_dir(__DIR__ . '/logs') || mkdir(__DIR__ . '/logs'));

$dotenv_file = __DIR__ . '/.env';
assert(file_exists($dotenv_file) || touch($dotenv_file), 'no env set');
$dotenv = Dotenv\Dotenv::createMutable(dirname($dotenv_file));
$dotenv->load();