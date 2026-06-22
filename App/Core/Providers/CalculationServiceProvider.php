<?php

namespace App\Core\Providers;

use App\Contracts\ProviderInterface;
use App\Core\Container;
use App\Core\Database;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\CdiRateService;
use App\Services\DailyReportService;
use App\Services\InvestmentService;
use App\Services\ProfitCalculationService;
use App\Services\RateCalculationService;
use App\Services\SelicService;
use App\Services\TaxCalculationService;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Contracts\InvestmentRepositoryInterface;
use App\Repositories\CreateInvestmentRepository;
use App\UseCases\CalculateInvestmentUseCase;
use App\UseCases\SelicUseCase;

class CalculationServiceProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->bind(CdiRateService::class, fn() => new CdiRateService(
            pdo: Database::getConnection()
        ), true);

        $container->bind(
            InvestmentService::class,
            fn($c) => new InvestmentService(
                rateService: $c->getInstancia(RateCalculationService::class),
                taxService: $c->getInstancia(TaxCalculationService::class),
                profitService: $c->getInstancia(ProfitCalculationService::class),
                businessDayService: $c->getInstancia(BusinessDayService::class),
                formatter: $c->getInstancia(AmountFormatterService::class),
                calculationInvestment: $c->getInstancia(InvestmentCalculation::class),
                repository: $c->getInstancia(InvestmentRepositoryInterface::class),
                createInvestmentRepository: new CreateInvestmentRepository(Database::getConnection()),
            ),
            true
        );

        $container->bind(
            CalculateInvestmentUseCase::class,
            fn($c) => new CalculateInvestmentUseCase(
                service: $c->getInstancia(InvestmentService::class),
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

        $container->bind(SelicService::class, fn() => new SelicService(), true);

        $container->bind(
            SelicUseCase::class,
            fn($c) => new SelicUseCase(
                $c->getInstancia(SelicService::class),
            ),
            true
        );
    }
}
