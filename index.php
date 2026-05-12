<?php
require __DIR__ . '/bootstrap.php';

$argv = $_SERVER['argv'] ?? [];

try {
    $controller = $container->getInstancia(\App\Controllers\AppController::class);
    if (method_exists($controller, 'execute')) {
        $controller->execute($argv);
    }
} catch (\Exception $exception) {
    fwrite(STDERR, $exception->getMessage() . PHP_EOL);
    exit(1);
}