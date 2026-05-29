<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Config;
use App\Core\Container;
use App\Core\AppServiceProvider;

Config::load(__DIR__);

date_default_timezone_set(Config::timezone());

function init(){
    $container = Container::getContainer();
    (new AppServiceProvider())->register($container);
    return $container;
}

$container = init();

