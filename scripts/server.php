<?php
require __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$port = $_ENV['APP_PORT'] ?? 8080;
$host = $argv[1] ?? 'localhost';

shell_exec("php -S {$host}:{$port} -t src"); 