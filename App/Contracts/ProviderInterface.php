<?php

namespace App\Contracts;

use App\Core\Container;

interface ProviderInterface
{
    public function register(Container $container): void;
}
