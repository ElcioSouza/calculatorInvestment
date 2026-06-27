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
            $page    = max(1, (int) ($params['page'] ?? 1));
            $perPage = max(1, min(100, (int) ($params['per_page'] ?? 10)));

            $paginated = $this->listUseCase->paginated($page, $perPage);

            $list = [];
            foreach ($paginated['data'] as $item) {
                $list[] = array_merge(
                    ['id' => $item['id']],
                    $this->buildPayload($item['input'], $item['result'])
                );
            }

            $total    = $paginated['total'];
            $lastPage = (int) ceil($total / $perPage);

            $this->jsonResponse(200, [
                'data' => $list,
                'pagination' => [
                    'total'        => $total,
                    'per_page'     => $perPage,
                    'current_page' => $page,
                    'last_page'    => $lastPage,
                    'has_more'     => $page < $lastPage,
                ],
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }

        return null;
    }
}
