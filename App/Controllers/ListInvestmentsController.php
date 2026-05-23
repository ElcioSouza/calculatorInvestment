<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\UseCases\ListInvestmentsUseCase;

class ListInvestmentsController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private ListInvestmentsUseCase $listUseCase,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $all  = $this->listUseCase->execute();
            $list = [];

            foreach ($all as $item) {
                $list[] = array_merge(
                    ['id' => $item['id']],
                    $this->buildPayload($item['input'], $item['result'])
                );
            }

            $this->jsonResponse(200, $list);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
