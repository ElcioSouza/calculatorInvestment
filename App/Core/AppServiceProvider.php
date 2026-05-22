<?php
namespace App\Core;

use App\Application\CliApplication;
use App\Application\HttpApplication;
use App\Controllers\ApiController;
use App\Controllers\CalculateController; // mudou
use App\Controllers\CliController;
use App\Controllers\InvestmentResultController; // mudou
use App\Factories\HttpInputFactory;
use App\Factories\InvestmentInputFactory;
use App\Helpers\DefaultInvestmentCalculationHelper as DefaultInvestmentCalculation;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Presenters\InvestmentPresenter;
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
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(), true);
        $container->bind(CdiRateService::class, fn() => new CdiRateService(), true);
        $container->bind(InvestmentInputFactory::class, fn($c) => new InvestmentInputFactory(
            $c->getInstancia(CdiRateService::class)
        ), true);
        $container->bind(InvestmentCalculation::class, fn() => new DefaultInvestmentCalculation(), true);
        $container->bind(RateCalculationService::class, fn() => new RateCalculationService(), true);
        $container->bind(TaxCalculationService::class, fn() => new TaxCalculationService(), true);

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

        $container->bind(
            InvestmentInputFactory::class,
            fn($c) => new InvestmentInputFactory(
                $c->getInstancia(CdiRateService::class)
            ),
            true
        );

        $container->bind(
            HttpInputFactory::class,
            fn($c) => new HttpInputFactory(
                $c->getInstancia(CdiRateService::class)
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

        $container->bind(
            \App\Contracts\InvestmentRepositoryInterface::class,
            fn() => new \App\Repositories\InMemoryInvestmentRepository(),
            true
        );

        $container->bind(
            ApiController::class,
            fn($c) => new ApiController(
                $c->getInstancia(HttpInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class),
                $c->getInstancia(\App\Contracts\InvestmentRepositoryInterface::class),
            ),
            true
        );

        $container->bind(CliApplication::class, fn($c) => new CliApplication(
            $c->getInstancia(CalculateController::class),
            $c->getInstancia(InvestmentResultController::class),
            $c->getInstancia(DailyReportService::class),
        ), true);

        $container->bind(CliController::class, fn($c) => new CliController(
            $c->getInstancia(CliApplication::class)
        ), true);

        $container->bind(
            HttpApplication::class,
            fn($c) => new HttpApplication(
                $c->getInstancia(ApiController::class),
            ),
            true
        );

    }
}
