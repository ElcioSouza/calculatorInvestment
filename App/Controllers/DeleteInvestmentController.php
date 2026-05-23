<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\UseCases\DeleteInvestmentUseCase;

class DeleteInvestmentController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private DeleteInvestmentUseCase $deleteUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $id      = $params['id'] ?? null;
            $deleted = $this->deleteUseCase->execute($id);

            if (!$deleted) {
                $this->jsonResponse(404, ['error' => "Investimento '{$id}' não encontrado."]);
                return null;
            }

            $this->jsonResponse(200, [
                'message' => "Investimento '{$id}' removido com sucesso.",
                'id'      => $id,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
