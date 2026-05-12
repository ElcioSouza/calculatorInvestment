<?php

namespace App\Controllers;

class AppController 
{
    public function __construct(private CalculateController $calculateController) {}

    public function execute(array $argv): mixed
    {
        $data = $this->calculateController->execute($argv);
        return null;
    }
}