<?php

namespace App\Core\Providers;

use App\Contracts\ProviderInterface;
use App\Core\Container;
use App\Application\CliApplication;
use App\Controllers\CalculateController;
use App\Controllers\CliController;
use App\Controllers\InvestmentResultController;
use App\Factories\InvestmentInputFactory;
use App\Presenters\InvestmentPresenter;
use App\Services\CdiRateService;
use App\Services\DailyReportService;
use App\Services\RateCalculationService;
use App\UseCases\CalculateInvestmentUseCase;

class CliServiceProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->bind(
            InvestmentInputFactory::class,
            fn($c) => new InvestmentInputFactory(
                $c->getInstancia(CdiRateService::class),
                $c->getInstancia(RateCalculationService::class),
            ),
            true
        );

        $container->bind(InvestmentPresenter::class, fn() => new InvestmentPresenter(), true);

        $container->bind(InvestmentResultController::class, fn($c) => new InvestmentResultController(
            $c->getInstancia(InvestmentPresenter::class)
        ), true);

        $container->bind(CalculateController::class, fn($c) => new CalculateController(
            $c->getInstancia(InvestmentInputFactory::class),
            $c->getInstancia(CalculateInvestmentUseCase::class),
            $c->getInstancia(CdiRateService::class),
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
