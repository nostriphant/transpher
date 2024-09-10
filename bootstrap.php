<?php

require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);

assert(is_dir(ROOT_DIR . '/logs') || mkdir(ROOT_DIR . '/logs'));

$dotenv_file = ROOT_DIR . '/.env';
assert(file_exists($dotenv_file) || touch($dotenv_file), 'no env set');
$dotenv = Dotenv\Dotenv::createMutable(dirname($dotenv_file));
$dotenv->load();