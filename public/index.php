<?php

declare(strict_types=1);

use Siro\Core\App;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/vendor/autoload.php';

$app = new App(BASE_PATH);
$app->boot();
$app->loadRoutes(BASE_PATH . '/routes/api.php');
$app->run();
