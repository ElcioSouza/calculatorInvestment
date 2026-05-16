<?php
namespace App\Core;

use App\Controllers\AppController;
use App\Controllers\CalculateController;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\TaxCalculationService;
class AppServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(AmountFormatterService::class, fn() => new AmountFormatterService(), true);
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(),true);
        $container->bind(RateCalculationService::class, fn() => new RateCalculationService());
        $container->bind(TaxCalculationService::class, fn() => new TaxCalculationService());
        
        $container->bind(
            ProfitCalculationService::class,
            fn($c) => new ProfitCalculationService(
                $c->get(TaxCalculationService::class),
                $c->get(AmountFormatterService::class)
            ),
          true
        );

        $container->bind(CalculateController::class, fn() => new CalculateController(), true);

        $container->bind(AppController::class, fn($c) => new AppController(
            calculateController: $c->getInstancia(CalculateController::class)
        ), true);
    }
}
