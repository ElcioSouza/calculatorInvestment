<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\UseCases\ShowInvestmentUseCase;

class ShowInvestmentController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private ShowInvestmentUseCase $showUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $id   = $params['id'] ?? null;
            $item = $this->showUseCase->execute($id);

            if ($item === null) {
                $this->jsonResponse(404, ['error' => "Investimento '{$id}' não encontrado."]);
                return null;
            }

            $this->jsonResponse(200, array_merge(
                ['id' => $item['id']],
                $this->buildPayload($item['input'], $item['result'])
            ));
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
