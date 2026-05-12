<?php

date_default_timezone_set('America/Sao_Paulo');

require_once __DIR__ . '/vendor/autoload.php';

function init(){
    $container = App\Core\Container::getContainer();
    (new App\Core\AppServiceProvider())->register($container);
    return $container;
}

$container = init();

