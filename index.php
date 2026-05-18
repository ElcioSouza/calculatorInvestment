<?php
require __DIR__ . '/bootstrap.php';
use App\Controllers\CliController;

$argv = $_SERVER['argv'] ?? [];

try {
    $controller = $container->getInstancia(CliController::class);
    if (method_exists($controller, 'execute')) {
        $controller->execute($argv);
    }
} catch (\Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}