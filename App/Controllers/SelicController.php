<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\UseCases\SelicUseCase;

class SelicController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private SelicUseCase $selicUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $result = $this->selicUseCase->execute();

            if ($result === null) {
                $this->jsonResponse(503, ['error' => 'Não foi possível obter a taxa Selic atual.']);
                return null;
            }

            $this->jsonResponse(200, [
                'selic_meta' => (float) $result['rate'],
                'date'       => $result['date'],
                'source'     => $result['source'],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
