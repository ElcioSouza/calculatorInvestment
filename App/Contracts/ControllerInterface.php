<?php

namespace App\Contracts;

interface ControllerInterface
{
    public function execute(array $argv): mixed;
}