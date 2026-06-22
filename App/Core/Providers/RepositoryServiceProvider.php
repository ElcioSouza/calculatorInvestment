<?php

namespace App\Core\Providers;

use App\Contracts\ProviderInterface;
use App\Core\Container;
use App\Core\Database;
use App\Contracts\InvestmentRepositoryInterface;
use App\Repositories\ListInvestmentRepository;
use App\Repositories\ShowInvestmentRepository;
use App\Services\DeleteInvestmentService;
use App\Services\ListInvestmentService;
use App\Services\ShowInvestmentService;
use App\UseCases\DeleteInvestmentUseCase;
use App\UseCases\ListInvestmentsUseCase;
use App\UseCases\ShowInvestmentUseCase;

class RepositoryServiceProvider implements ProviderInterface
{
    public function register(Container $container): void
    {
        $container->bind(
            ListInvestmentRepository::class,
            fn() => new ListInvestmentRepository(Database::getConnection()),
            false
        );

        $container->bind(
            ShowInvestmentRepository::class,
            fn() => new ShowInvestmentRepository(Database::getConnection()),
            false
        );

        $container->bind(
            ListInvestmentService::class,
            fn($c) => new ListInvestmentService(
                jsonRepository: $c->getInstancia(InvestmentRepositoryInterface::class),
                mysqlRepository: $c->getInstancia(ListInvestmentRepository::class),
            ),
            true
        );

        $container->bind(
            ShowInvestmentService::class,
            fn($c) => new ShowInvestmentService(
                jsonRepository: $c->getInstancia(InvestmentRepositoryInterface::class),
                mysqlRepository: $c->getInstancia(ShowInvestmentRepository::class),
            ),
            true
        );

        $container->bind(
            ListInvestmentsUseCase::class,
            fn($c) => new ListInvestmentsUseCase(
                $c->getInstancia(ListInvestmentService::class),
            ),
            true
        );

        $container->bind(
            ShowInvestmentUseCase::class,
            fn($c) => new ShowInvestmentUseCase(
                $c->getInstancia(ShowInvestmentService::class),
            ),
            true
        );

        $container->bind(
            DeleteInvestmentService::class,
            fn($c) => new DeleteInvestmentService(
                $c->getInstancia(InvestmentRepositoryInterface::class),
                new \App\Repositories\DeleteInvestmentRepository(Database::getConnection()),
            ),
            true
        );

        $container->bind(
            DeleteInvestmentUseCase::class,
            fn($c) => new DeleteInvestmentUseCase(
                $c->getInstancia(DeleteInvestmentService::class),
            ),
            true
        );
    }
}
