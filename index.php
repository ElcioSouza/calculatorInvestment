<?php
require __DIR__ . '/bootstrap.php';

use App\Controllers\CliController;
use App\Application\HttpApplication;

$isCli = in_array(PHP_SAPI, ['cli', 'phpdbg'], true);

try {
    if ($isCli) {
        $argv = $_SERVER['argv'] ?? [];
        $controller = $container->getInstancia(CliController::class);
        $controller->execute($argv);
    } else {
        $app = $container->getInstancia(HttpApplication::class);
        $app->handle();
    }
} catch (\Exception $exception) {
    if ($isCli) {
        fwrite(STDERR, $exception->getMessage() . PHP_EOL);
        exit(1);
    } else {
        http_response_code(500);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => $exception->getMessage()], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}