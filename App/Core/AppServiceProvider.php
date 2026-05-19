<?php
namespace App\Core;

use App\Controllers\CliController;
use App\Controllers\CalculateController;
use App\Controllers\InvestmentResultController;
use App\Factories\InvestmentInputFactory;
use App\Helpers\DefaultInvestmentCalculationHelper as DefaultInvestmentCalculation;
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
use App\Presenters\InvestmentPresenter;
use App\Application\CliApplication;
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
        $container->bind(InvestmentCalculation::class, fn() => new DefaultInvestmentCalculation(), true);
        $container->bind(RateCalculationService::class, fn() => new RateCalculationService(),true);
        $container->bind(TaxCalculationService::class, fn() => new TaxCalculationService(),true);
        
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
            ),
            true
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
            ),
            true
        );

        $container->bind(CalculateController::class, fn($c) => new CalculateController(
                $c->getInstancia(InvestmentInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class)
        ), true);

        
        $container->bind(InvestmentPresenter::class, fn() => new InvestmentPresenter(), true);


        $container->bind(InvestmentResultController::class, fn($c) => new InvestmentResultController(
            $c->getInstancia(InvestmentPresenter::class)
        ), true);

        $container->bind(CliApplication::class, fn($c) => new CliApplication(
            $c->getInstancia(CalculateController::class),
            $c->getInstancia(InvestmentResultController::class),
            $c->getInstancia(DailyReportService::class),
        ), true);

        $container->bind(CliController::class, fn($c) => new CliController(
            $c->getInstancia(CliApplication::class)
        ), true);
    }
}
