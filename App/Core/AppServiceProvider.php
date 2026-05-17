<?php
namespace App\Core;

use App\Controllers\AppController;
use App\Controllers\CalculateController;
use App\Helpers\DefaultInvestmentCalculationHelper;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\TaxCalculationService;
class AppServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(AmountFormatterService::class, fn() => new AmountFormatterService(), true);
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(),true);
        $container->bind(InvestmentCalculation::class, fn() => new DefaultInvestmentCalculationHelper(), true);
        $container->bind(RateCalculationService::class, fn() => new RateCalculationService());
        $container->bind(TaxCalculationService::class, fn() => new TaxCalculationService());
        
        $container->bind(
            ProfitCalculationService::class,
            fn($c) => new ProfitCalculationService(
                $c->getInstancia(TaxCalculationService::class),
                $c->getInstancia(AmountFormatterService::class)
            ),
          true
        );

        $container->bind(
            InvestmentService::class,
            fn($c) => new InvestmentService(
                rateService: $c->getInstancia(RateCalculationService::class),
                taxService: $c->getInstancia(TaxCalculationService::class),
                profitService: $c->getInstancia(ProfitCalculationService::class),
                businessDayService: $c->getInstancia(BusinessDayService::class),
                formatter: $c->getInstancia(AmountFormatterService::class),
                calculationInvestment: $c->getInstancia(InvestmentCalculation::class),
                repository: new InMemoryInvestmentRepository(),
            ),
            true
        );

        $container->bind(
            CalculateInvestmentUseCase::class,
            fn($c) => new CalculateInvestmentUseCase(
                service: $c->getInstancia(InvestmentService::class)
            )
        );

        $container->bind(
            DailyReportService::class,
            fn($c) => new DailyReportService(
                $c->getInstancia(RateCalculationService::class),
                $c->getInstancia(BusinessDayService::class),
                $c->getInstancia(TaxCalculationService::class),
                $c->getInstancia(ProfitCalculationService::class),
                $c->getInstancia(InvestmentCalculation::class),
                $c->getInstancia(AmountFormatterService::class),
            )
        );

        $container->bind(CalculateController::class, fn() => new CalculateController(
            new InvestmentInputFactory(),
            $c->getInstancia(CalculateInvestmentUseCase::class)
        ), true);

        $container->bind(AppController::class, fn($c) => new AppController(
            calculateController: $c->getInstancia(CalculateController::class)
        ), true);
    }
}
