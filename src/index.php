<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$container = new Container();
$app = AppFactory::createFromContainer($container);

$app->addErrorMiddleware(true, true, true);

// Add routes
require __DIR__ . '/Routes/Api.php';

// Run app
$app->run();
