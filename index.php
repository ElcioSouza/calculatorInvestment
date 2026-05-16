<?php
require __DIR__ . '/bootstrap.php';
use App\Controllers\AppController;

$argv = $_SERVER['argv'] ?? [];

try {
    $controller = $container->getInstancia(AppController::class);
    if (method_exists($controller, 'execute')) {
        $controller->execute($argv);
    }
} catch (\Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}