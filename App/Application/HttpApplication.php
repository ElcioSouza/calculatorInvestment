<?php
namespace App\Application;

use App\Controllers\CalculateInvestmentEstimateController;
use App\Controllers\CreateInvestmentController;
use App\Controllers\DeleteInvestmentController;
use App\Controllers\ListInvestmentsController;
use App\Controllers\SelicController;
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
        private CalculateInvestmentEstimateController $estimateController,
        private SelicController $selicController,
    ) {}

    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path   = rtrim($path, '/');

        try {
            $params = $this->resolveParams($method);
        } catch (\RuntimeException $e) {
            http_response_code(413);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['error' => $e->getMessage()]);
            return;
        }

        if ($path === '' || $path === '/') {
            $this->jsonResponse(200, [
                'message' => 'Calculator Investment API',
                'routes'  => $this->getRoutes(),
            ]);
            return;
        }

        if ($path === '/api/selic') {
            $this->selicController->execute($params);
            return;
        }

        if ($path === '/api/calculate') {
            $hasInvestmentParams = isset($params['investment_type'])
                || isset($params['rate_type'])
                || isset($params['capital'])
                || isset($params['application_date'])
                || isset($params['months']);

            if ($method === 'GET' && $hasInvestmentParams) {
                $this->estimateController->execute($params);
                return;
            }

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
            $id = urldecode($matches[1]);
            if (!ctype_digit($id)) {
                http_response_code(400);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['error' => 'ID deve ser numérico.']);
                return;
            }
            $params['id'] = $id;

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
            'error' => 'Rota não encontrada.',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function resolveParams(string $method): array
    {
        $params = $_GET ?? [];

        if (in_array($method, ['POST', 'PUT'], true)) {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

            if (str_contains($contentType, 'application/json')) {
                $raw = file_get_contents('php://input');
                if (strlen($raw) > 10240) {
                    throw new \RuntimeException('Body excede o tamanho máximo de 10KB.');
                }
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

    private function jsonResponse(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }

    private function getRoutes(): array
    {
        return [
            [
                'method'      => 'GET',
                'path'        => '/api/calculate',
                'description' => 'Lista todos os investimentos (com paginação)',
                'params'      => ['page' => 'int (default: 1)', 'per_page' => 'int (default: 10, max: 100)'],
            ],
            [
                'method'      => 'GET',
                'path'        => '/api/calculate?id={id}',
                'description' => 'Busca investimento por ID',
            ],
            [
                'method'      => 'GET',
                'path'        => '/api/calculate/{id}',
                'description' => 'Busca investimento por ID (via path)',
            ],
            [
                'method'      => 'GET',
                'path'        => '/api/calculate?investment_type={type}&...',
                'description' => 'Simula cálculo de investimento (sem persistir)',
                'params'      => [
                    'investment_type' => 'cdb | cdi | selic | pre',
                    'rate_type'       => 'pos | pre',
                    'capital'         => 'float',
                    'application_date'=> 'YYYY-MM-DD',
                    'redemption_date' => 'YYYY-MM-DD',
                    'months'          => 'int',
                    'cdi_percentage'  => 'float (opcional)',
                    'pre_fixed_rate'  => 'float (opcional)',
                ],
            ],
            [
                'method'      => 'POST',
                'path'        => '/api/calculate',
                'description' => 'Cria e persiste um novo investimento',
            ],
            [
                'method'      => 'PUT',
                'path'        => '/api/calculate/{id}',
                'description' => 'Atualiza e recalcula investimento existente',
            ],
            [
                'method'      => 'DELETE',
                'path'        => '/api/calculate/{id}',
                'description' => 'Remove investimento por ID',
            ],
            [
                'method'      => 'GET',
                'path'        => '/api/selic',
                'description' => ' Retorna a taxa Selic Meta atual (BCB)',
            ],
        ];
    }
}
