<?php
namespace App\Application;

use App\Controllers\ApiController;

class HttpApplication
{
    public function __construct(
        private ApiController $apiController,
    ) {}

    public function handle(): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $path   = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
        $path   = rtrim($path, '/');

        $params = $this->resolveParams($method);

        if ($path === '/api/calculate') {
            if (!in_array($method, ['GET', 'POST'], true)) {
                $this->methodNotAllowed('GET, POST');
                return;
            }
            $this->apiController->calculate($params);
            return;
        }

        if (preg_match('#^/api/calculate/([^/]+)$#', $path, $matches)) {
            $id = urldecode($matches[1]);

            match ($method) {
                'PUT'    => $this->apiController->update($id, $params),
                'DELETE' => $this->apiController->destroy($id),
                default  => $this->methodNotAllowed('PUT, DELETE'),
            };
            return;
        }

        http_response_code(404);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'error'  => 'Rota não encontrada.',
            'routes' => [
                'GET  /api/calculate'        => 'Calcula novo investimento via query string',
                'POST /api/calculate'        => 'Calcula novo investimento via body (JSON ou form)',
                'PUT  /api/calculate/{id}'   => 'Recalcula substituindo registro existente',
                'DELETE /api/calculate/{id}' => 'Remove registro existente',
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
