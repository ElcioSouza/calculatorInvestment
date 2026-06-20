<?php
namespace App\Core;

use App\Application\CliApplication;
use App\Application\HttpApplication;
use App\Controllers\CalculateController;
use App\Controllers\CalculateInvestmentEstimateController;
use App\Core\Database;
use App\Controllers\CliController;
use App\Controllers\CreateInvestmentController;
use App\Controllers\DeleteInvestmentController;
use App\Controllers\InvestmentResultController;
use App\Controllers\ListInvestmentsController;
use App\Controllers\SelicController;
use App\Controllers\ShowInvestmentController;
use App\Controllers\UpdateInvestmentController;
use App\Factories\HttpInputFactory;
use App\Factories\InvestmentInputFactory;
use App\Helpers\DefaultInvestmentCalculationHelper as DefaultInvestmentCalculation;
use App\Helpers\InvestmentCalculationHelper as InvestmentCalculation;
use App\Presenters\InvestmentPresenter;
use App\Repositories\CreateInvestmentRepository;
use App\Repositories\DeleteInvestmentRepository;
use App\Repositories\ListInvestmentRepository;
use App\Repositories\ShowInvestmentRepository;
use App\Services\AmountFormatterService;
use App\Services\BusinessDayService;
use App\Services\CdiRateService;
use App\Services\DailyReportService;
use App\Services\DeleteInvestmentService;
use App\Services\InvestmentService;
use App\Services\ListInvestmentService;
use App\Services\SelicService;
use App\Services\ShowInvestmentService;
use App\Services\ProfitCalculationService;
use App\Services\RateCalculationService;
use App\Services\TaxCalculationService;
use App\UseCases\CalculateInvestmentUseCase;
use App\UseCases\DeleteInvestmentUseCase;
use App\UseCases\ListInvestmentsUseCase;
use App\UseCases\SelicUseCase;
use App\UseCases\ShowInvestmentUseCase;

class AppServiceProvider
{
    public function register(Container $container): void
    {
        $container->bind(
            \App\Contracts\InvestmentRepositoryInterface::class,
            fn() => new \App\Repositories\JsonFileInvestmentRepository(),
            true
        );

        $container->bind(AmountFormatterService::class, fn() => new AmountFormatterService(), true);
        $container->bind(BusinessDayService::class, fn() => new BusinessDayService(), true);
        $container->bind(CdiRateService::class, fn() => new CdiRateService(
            pdo: Database::getConnection()
        ), true);
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

        $container->bind(
            InvestmentService::class,
            fn($c) => new InvestmentService(
                rateService: $c->getInstancia(RateCalculationService::class),
                taxService: $c->getInstancia(TaxCalculationService::class),
                profitService: $c->getInstancia(ProfitCalculationService::class),
                businessDayService: $c->getInstancia(BusinessDayService::class),
                formatter: $c->getInstancia(AmountFormatterService::class),
                calculationInvestment: $c->getInstancia(InvestmentCalculation::class),
                repository: $c->getInstancia(\App\Contracts\InvestmentRepositoryInterface::class),
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
                $c->getInstancia(CdiRateService::class),
                $c->getInstancia(RateCalculationService::class)
            ),
            true
        );

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
                jsonRepository: $c->getInstancia(\App\Contracts\InvestmentRepositoryInterface::class),
                mysqlRepository: $c->getInstancia(ListInvestmentRepository::class),
            ),
            true
        );

        $container->bind(
            ShowInvestmentService::class,
            fn($c) => new ShowInvestmentService(
                jsonRepository: $c->getInstancia(\App\Contracts\InvestmentRepositoryInterface::class),
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
                $c->getInstancia(\App\Contracts\InvestmentRepositoryInterface::class),
                new DeleteInvestmentRepository(Database::getConnection()),
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

        $container->bind(SelicService::class, fn() => new SelicService(), true);

        $container->bind(
            SelicUseCase::class,
            fn($c) => new SelicUseCase(
                $c->getInstancia(SelicService::class),
            ),
            true
        );

        $container->bind(CalculateController::class, fn($c) => new CalculateController(
            $c->getInstancia(InvestmentInputFactory::class),
            $c->getInstancia(CalculateInvestmentUseCase::class),
            $c->getInstancia(CdiRateService::class),
        ), true);

        $container->bind(InvestmentPresenter::class, fn() => new InvestmentPresenter(), true);

        $container->bind(InvestmentResultController::class, fn($c) => new InvestmentResultController(
            $c->getInstancia(InvestmentPresenter::class)
        ), true);

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
