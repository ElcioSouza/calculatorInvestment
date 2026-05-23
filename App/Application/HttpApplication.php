<?php
namespace App\Application;

use App\Controllers\CreateInvestmentController;
use App\Controllers\DeleteInvestmentController;
use App\Controllers\ListInvestmentsController;
use App\Controllers\ShowInvestmentController;
use App\Controllers\UpdateInvestmentController;

class HttpApplication
{
    public function __construct(
        private ListInvestmentsController $listController,
        private ShowInvestmentController $showController,
        private CreateInvestmentController $createController,
        private UpdateInvestmentController $updateController,
        private DeleteInvestmentController $deleteController,
    ) {}

    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path   = rtrim($path, '/');

        $params = $this->resolveParams($method);

        if ($path === '/api/calculate') {
            $hasInvestmentParams = isset($params['investment_type'])
                || isset($params['rate_type'])
                || isset($params['capital'])
                || isset($params['application_date'])
                || isset($params['months']);

            if ($method === 'GET' && !$hasInvestmentParams) {
                if (isset($params['id'])) {
                    $this->showController->execute($params);
                    return;
                }
                $this->listController->execute($params);
                return;
            }

            if ($method === 'PUT' && isset($params['id'])) {
                $this->updateController->execute($params);
                return;
            }

            if ($method === 'DELETE' && isset($params['id'])) {
                $this->deleteController->execute($params);
                return;
            }

            if (!in_array($method, ['GET', 'POST'], true)) {
                $this->methodNotAllowed('GET, POST');
                return;
            }
            $this->createController->execute($params);
            return;
        }

        if (preg_match('#^/api/calculate/([^/]+)$#', $path, $matches)) {
            $params['id'] = urldecode($matches[1]);

            match ($method) {
                'GET'    => $this->showController->execute($params),
                'PUT'    => $this->updateController->execute($params),
                'DELETE' => $this->deleteController->execute($params),
                default  => $this->methodNotAllowed('GET, PUT, DELETE'),
            };
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'  => 'Rota não encontrada.',
            'routes' => [
                'GET    /api/calculate'           => 'Lista todos os investimentos cadastrados',
                'GET    /api/calculate?params...' => 'Calcula novo investimento via query string',
                'POST   /api/calculate'           => 'Calcula novo investimento via body (JSON ou form)',
                'GET    /api/calculate/{id}'      => 'Busca investimento por ID',
                'PUT    /api/calculate/{id}'      => 'Recalcula substituindo registro existente',
                'DELETE /api/calculate/{id}'      => 'Remove registro existente',
            ],
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function resolveParams(string $method): array
    {
        $params = $_GET ?? [];

        if (in_array($method, ['POST', 'PUT'], true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $raw  = file_get_contents('php://input');
                $json = json_decode($raw, true);
                if (is_array($json)) {
                    $params = array_merge($params, $json);
                }
            } else {
                $params = array_merge($params, $_POST ?? []);
            }
        }

        return $params;
    }

    private function methodNotAllowed(string $allowed): void
    {
        http_response_code(405);
        header('Allow: ' . $allowed);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => "Método não permitido. Permitidos: {$allowed}."]);
    }
}
