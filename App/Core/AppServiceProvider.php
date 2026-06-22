<?php
namespace App\Core;

use App\Core\Providers\CalculationServiceProvider;
use App\Core\Providers\CliServiceProvider;
use App\Core\Providers\CoreServiceProvider;
use App\Core\Providers\HttpServiceProvider;
use App\Core\Providers\RepositoryServiceProvider;

class AppServiceProvider
{
    public function register(Container $container): void
    {
        (new CoreServiceProvider())->register($container);
        (new CalculationServiceProvider())->register($container);
        (new RepositoryServiceProvider())->register($container);
        (new HttpServiceProvider())->register($container);
        (new CliServiceProvider())->register($container);
    }
}
