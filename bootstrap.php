<?php

require_once __DIR__ . '/vendor/autoload.php';

define('ROOT_DIR', __DIR__);
is_dir(ROOT_DIR . '/logs') || mkdir(ROOT_DIR . '/logs');

$dotenv_file = ROOT_DIR . '/.env';
is_file($dotenv_file) || touch($dotenv_file);
$dotenv = Dotenv\Dotenv::createMutable(dirname($dotenv_file));
$dotenv->load();