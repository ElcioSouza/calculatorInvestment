<?php

namespace App\Application;

use App\Controllers\CalculateController;
use App\Controllers\InvestmentResultController;
use App\Services\DailyReportService;
use App\ValueObjects\Investment;

class CliApplication
{
    public function __construct(
        private CalculateController $calculateController,
        private InvestmentResultController $resultController,
        private DailyReportService $dailyReportService,
    ) {}

    public function execute(array $argv): Investment
    {
        $data = $this->calculateController->execute($argv);

        $this->resultController->execute($data);

        $this->dailyReportService->generate($data['input'], $data['result']);

        return $data['result'];
    }
}
