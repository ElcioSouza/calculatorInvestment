<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/vendor/autoload.php';

use App\Core\Container;  
use App\Core\AppServiceProvider;

function init(){
    $container = Container::getContainer();
    (new AppServiceProvider())->register($container);
    return $container;
}

$container = init();

