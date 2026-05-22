<?php
namespace App\Controllers;

use App\Contracts\InvestmentRepositoryInterface;
use App\Factories\HttpInputFactory;
use App\UseCases\CalculateInvestmentUseCase;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

class ApiController
{
    public function __construct(
        private HttpInputFactory $inputFactory,
        private CalculateInvestmentUseCase $calculateUseCase,
        private InvestmentRepositoryInterface $repository,
    ) {}

    public function calculate(array $params): void
    {
        try {
            $input  = $this->inputFactory->create($params);
            $result = $this->calculateUseCase->execute($input);

            $all  = $this->repository->all();
            $last = end($all);
            $id   = $last['id'] ?? null;

            $this->jsonResponse(201, array_merge(
                ['id' => $id],
                $this->buildPayload($input, $result)
            ));
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(422, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
    public function update(string $id, array $params): void
    {
        try {
            $existing = $this->repository->findById($id);

            if ($existing === null) {
                $this->jsonResponse(404, ['error' => "Investimento '{$id}' não encontrado."]);
                return;
            }

            $originalParams = $this->inputToParams($existing['input']);
            $merged         = array_merge($originalParams, array_filter($params, fn($v) => $v !== '' && $v !== null));

            $input  = $this->inputFactory->create($merged);
            $result = $this->calculateUseCase->execute($input);

            $this->repository->delete($id);
            $all   = $this->repository->all();
            $last  = end($all);
            $newId = $last['id'] ?? $id;

            $this->jsonResponse(200, array_merge(
                ['id' => $newId, 'replaced_id' => $id],
                $this->buildPayload($input, $result)
            ));
        } catch (\InvalidArgumentException $e) {
            $this->jsonResponse(422, ['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
    public function destroy(string $id): void
    {
        try {
            $deleted = $this->repository->delete($id);

            if (! $deleted) {
                $this->jsonResponse(404, ['error' => "Investimento '{$id}' não encontrado."]);
                return;
            }

            $this->jsonResponse(200, [
                'message' => "Investimento '{$id}' removido com sucesso.",
                'id' => $id,
            ]);
        } catch (\Throwable $e) {
            $this->jsonResponse(500, ['error' => 'Erro interno: ' . $e->getMessage()]);
        }
    }
    private function buildPayload(InvestmentInput $input, Investment $result): array
    {
        return [
            'input'  => [
                'investment_type'  => $input->investmentType,
                'rate_type'        => $input->rateType,
                'initial_capital'  => (float) $input->initialCapital,
                'cdi_percentage'   => $input->rateType !== 'pre' ? (float) $input->cdiPercentage : null,
                'selic_meta'       => $input->rateType !== 'pre' ? (float) $input->selicMeta : null,
                'pre_fixed_rate'   => $input->rateType === 'pre' ? (float) $input->preFixedAnnualRate : null,
                'cdi_over'         => $input->cdiOver !== '' ? (float) $input->cdiOver : null,
                'application_date' => $input->applicationDate,
                'redemption_date'  => $input->redemptionDate,
                'months'           => $input->months,
                'is_isento'        => $input->isIsento,
            ],
            'result' => [
                'amount_bruto'          => (float) $result->amountBruto,
                'amount_liquid'         => (float) $result->amountLiquid,
                'profit_bruto'          => (float) $result->profitBruto,
                'profit_liquid'         => (float) $result->profitLiquid,
                'iof_value'             => (float) $result->iofValue,
                'ir_tax_amount'         => (float) $result->irTaxAmount,
                'monthly_profit_liquid' => (float) $result->monthlyProfitLiquid,
                'daily_profit_display'  => (float) $result->dailyProfitDisplay,
                'days'                  => $result->days,
                'business_days'         => $result->businessDays,
                'is_isento'             => $result->isIsento,
            ],
        ];
    }
    private function inputToParams(InvestmentInput $input): array
    {
        return [
            'investment_type'  => $input->investmentType,
            'rate_type'        => $input->rateType,
            'application_date' => $input->applicationDate,
            'months'           => (string) $input->months,
            'capital'          => $input->initialCapital,
            'cdi'              => $input->cdiPercentage,
            'selic_meta'       => $input->selicMeta,
            'pre_rate'         => $input->preFixedAnnualRate,
            'cdi_annual'       => $input->cdiOver,
        ];
    }
    private function jsonResponse(int $status, array $data): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    }
}
