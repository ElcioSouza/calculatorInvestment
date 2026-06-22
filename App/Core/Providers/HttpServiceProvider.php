<?php

namespace App\Core\Providers;

use App\Contracts\ProviderInterface;
use App\Core\Container;
use App\Controllers\CalculateInvestmentEstimateController;
use App\Controllers\CreateInvestmentController;
use App\Controllers\DeleteInvestmentController;
use App\Controllers\ListInvestmentsController;
use App\Controllers\SelicController;
use App\Controllers\ShowInvestmentController;
use App\Controllers\UpdateInvestmentController;
use App\Factories\HttpInputFactory;
use App\Presenters\InvestmentPresenter;
use App\Services\CdiRateService;
use App\Services\RateCalculationService;
use App\UseCases\CalculateInvestmentUseCase;
use App\UseCases\DeleteInvestmentUseCase;
use App\UseCases\ListInvestmentsUseCase;
use App\UseCases\SelicUseCase;
use App\UseCases\ShowInvestmentUseCase;
use App\Application\HttpApplication;

class HttpServiceProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->bind(
            HttpInputFactory::class,
            fn($c) => new HttpInputFactory(
                $c->getInstancia(CdiRateService::class),
                $c->getInstancia(RateCalculationService::class)
            ),
            true
        );

        $container->bind(
            ListInvestmentsController::class,
            fn($c) => new ListInvestmentsController(
                $c->getInstancia(ListInvestmentsUseCase::class),
            ),
            true
        );

        $container->bind(
            ShowInvestmentController::class,
            fn($c) => new ShowInvestmentController(
                $c->getInstancia(ShowInvestmentUseCase::class),
            ),
            true
        );

        $container->bind(
            CreateInvestmentController::class,
            fn($c) => new CreateInvestmentController(
                $c->getInstancia(HttpInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class),
            ),
            true
        );

        $container->bind(
            CalculateInvestmentEstimateController::class,
            fn($c) => new CalculateInvestmentEstimateController(
                $c->getInstancia(HttpInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class),
            ),
            true
        );

        $container->bind(
            UpdateInvestmentController::class,
            fn($c) => new UpdateInvestmentController(
                $c->getInstancia(HttpInputFactory::class),
                $c->getInstancia(CalculateInvestmentUseCase::class),
                $c->getInstancia(ShowInvestmentUseCase::class),
            ),
            true
        );

        $container->bind(
            DeleteInvestmentController::class,
            fn($c) => new DeleteInvestmentController(
                $c->getInstancia(DeleteInvestmentUseCase::class),
            ),
            true
        );

        $container->bind(
            SelicController::class,
            fn($c) => new SelicController(
                $c->getInstancia(SelicUseCase::class),
            ),
            true
        );

        $container->bind(
            HttpApplication::class,
            fn($c) => new HttpApplication(
                $c->getInstancia(ListInvestmentsController::class),
                $c->getInstancia(ShowInvestmentController::class),
                $c->getInstancia(CreateInvestmentController::class),
                $c->getInstancia(UpdateInvestmentController::class),
                $c->getInstancia(DeleteInvestmentController::class),
                $c->getInstancia(CalculateInvestmentEstimateController::class),
                $c->getInstancia(SelicController::class),
            ),
            true
        );
    }
}
