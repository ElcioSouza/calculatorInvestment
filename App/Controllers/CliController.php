<?php

namespace App\Controllers;

use App\ValueObjects\Investment;

class CliController
{
    public function __construct(private CalculateController $calculateController) {}

    public function execute(array $argv): Investment
    {
        return $this->calculateController->execute($argv);
    }
}