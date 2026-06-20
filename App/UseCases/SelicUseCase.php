<?php

namespace App\UseCases;

use App\Services\SelicService;

class SelicUseCase
{
    public function __construct(
        private SelicService $service,
    ) {}

    public function execute(): ?array
    {
        return $this->service->execute();
    }
}
