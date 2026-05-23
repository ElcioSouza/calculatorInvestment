<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\Factories\HttpInputFactory;
use App\UseCases\CalculateInvestmentUseCase;
use App\UseCases\ShowInvestmentUseCase;

class UpdateInvestmentController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private HttpInputFactory $inputFactory,
        private CalculateInvestmentUseCase $calculateUseCase,
        private ShowInvestmentUseCase $showUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $id       = $params['id'] ?? null;
            $existing = $this->showUseCase->execute($id);

            if ($existing === null) {
                $this->jsonResponse(404, ['error' => "Investimento '{$id}' não encontrado."]);
                return null;
            }

            $originalParams = $this->inputFactory->inputToParams($existing['input']);
            $merged         = array_merge($originalParams, array_filter($params, fn($v) => $v !== '' && $v !== null));

            $input  = $this->inputFactory->create($merged);
            $result = $this->calculateUseCase->recalculateAndUpdate($id, $input);

            $this->jsonResponse(200, array_merge(
                ['id' => $id],
                $this->buildPayload($input, $result)
            ));
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(422, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
