<?php

namespace App\Controllers;

use App\ValueObjects\Investment;
class CliController
{
    public function __construct(private \App\Application\CliApplication $app) {}

    public function execute(array $argv): Investment
    {
        return $this->app->execute($argv);
    }
}