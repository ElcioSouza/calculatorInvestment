<?php

namespace App\Core\Providers;

use App\Contracts\ProviderInterface;
use App\Core\Container;
use App\Contracts\InvestmentRepositoryInterface;
use App\Helpers\DefaultInvestmentCalculationHelper as DefaultInvestmentCalculation;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\ProfitCalculationService;
use App\Services\RateCalculationService;
use App\Services\TaxCalculationService;
use App\Repositories\JsonFileInvestmentRepository;

class CoreServiceProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->bind(
            InvestmentRepositoryInterface::class,
            fn() => new JsonFileInvestmentRepository(),
            true
        );

        $container->bind(AmountFormatterService::class, fn() => new AmountFormatterService(), true);
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(), true);
        $container->bind(InvestmentCalculation::class, fn() => new DefaultInvestmentCalculation(), true);
        $container->bind(RateCalculationService::class, fn() => new RateCalculationService(), true);
        $container->bind(TaxCalculationService::class, fn() => new TaxCalculationService(), true);

        $container->bind(
            ProfitCalculationService::class,
            fn($c) => new ProfitCalculationService(
                $c->getInstancia(TaxCalculationService::class)
            ),
            true
        );
    }
}
