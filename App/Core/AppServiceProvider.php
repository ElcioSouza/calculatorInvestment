<?php
namespace App\Core;

use App\Controllers\CliController;
use App\Controllers\CalculateController;
use App\Factories\InvestmentInputFactory;
use App\Helpers\DefaultInvestmentCalculationHelper;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Repositories\InMemoryInvestmentRepository;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\CdiRateService;
use App\Services\DailyReportService;
use App\Services\InvestmentService;
use App\Services\ProfitCalculationService;
use App\Services\RateCalculationService;
use App\Services\TaxCalculationService;
use App\UseCases\CalculateInvestmentUseCase;
class AppServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(AmountFormatterService::class, fn() => new AmountFormatterService(), true);
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(),true);
        $container->bind(CdiRateService::class, fn() => new CdiRateService(), true);
        $container->bind(InvestmentInputFactory::class, fn($c) => new InvestmentInputFactory(
            $c->getInstancia(CdiRateService::class)
        ), true);
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

        $container->bind(CalculateController::class, fn($c) => new CalculateController(
                $c->getInstancia(InvestmentInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class)
        ), true);

        $container->bind(CliController::class, fn($c) => new CliController(
            calculateController: $c->getInstancia(CalculateController::class)
        ), true);
    }
}
