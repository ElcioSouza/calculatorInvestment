<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\Factories\HttpInputFactory;
use App\UseCases\CalculateInvestmentUseCase;

class CreateInvestmentController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private HttpInputFactory $inputFactory,
        private CalculateInvestmentUseCase $calculateUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $input  = $this->inputFactory->create($params);
            $result = $this->calculateUseCase->execute($input);
            $id     = $this->calculateUseCase->getLastId();

            $this->jsonResponse(201, array_merge(
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
