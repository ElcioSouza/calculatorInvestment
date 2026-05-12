<?php
namespace App\Core;

use App\Controllers\AppController;
use App\Controllers\CalculateController;

class AppServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(CalculateController::class, fn() => new CalculateController());

        $container->bind(AppController::class, fn($c) => new AppController(
            calculateController: $c->getInstancia(CalculateController::class)
        ), true);
    }
}
