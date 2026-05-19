<?php

namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\Presenters\InvestmentPresenter;
use App\ValueObjects\InvestmentInput;
use App\ValueObjects\InvestmentResult;

class InvestmentResultController implements ControllerInterface
{
    public function __construct(
        private InvestmentPresenter $presenter,
    ) {}

    public function execute(array $payload): mixed
    {
        $this->presenter->display($payload['input'], $payload['result']);
        return null;
    }
}
