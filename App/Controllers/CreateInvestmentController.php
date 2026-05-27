<?php
namespace App\Controllers;

use App\Contracts\ControllerInterface;
use App\Factories\HttpInputFactory;
use App\Repositories\CreateInvestmentRepository;
use App\UseCases\CalculateInvestmentUseCase;

class CreateInvestmentController extends BaseApiController implements ControllerInterface
{
    public function __construct(
        private HttpInputFactory $inputFactory,
        private CalculateInvestmentUseCase $calculateUseCase,
        private CreateInvestmentRepository $mysqlRepository,
    ) {}

    public function execute(array $params): mixed
    {
        try {
            $input  = $this->inputFactory->create($params);
            $result = $this->calculateUseCase->execute($input);

            $id = $this->mysqlRepository->insertInvestment([
                'initial_capital'       => $input->initialCapital,
                'investment_type'       => $input->investmentType,
                'rate_type'             => $input->rateType,
                'cdi_percentage'        => $input->cdiPercentage !== '' ? $input->cdiPercentage : '0',
                'selic_meta'            => $input->selicMeta !== '' ? $input->selicMeta : '0',
                'pre_fixed_annual_rate' => $input->preFixedAnnualRate !== '' ? $input->preFixedAnnualRate : '0',
                'application_date'      => $input->applicationDate,
                'redemption_date'       => $input->redemptionDate,
                'months'                => $input->months,
                'selic_is_over'         => $input->selicIsOver,
                'cdi_over'              => $input->cdiOver,
            ]);

            $this->mysqlRepository->insertEstimate($id, [
                'amount_bruto'          => $result->amountBruto,
                'amount_liquid'         => $result->amountLiquid,
                'profit_bruto'          => $result->profitBruto,
                'profit_liquid'         => $result->profitLiquid,
                'iof_value'             => $result->iofValue,
                'ir_tax_amount'         => $result->irTaxAmount,
                'monthly_profit_liquid' => $result->monthlyProfitLiquid,
                'daily_profit_display'  => $result->dailyProfitDisplay,
                'is_isento'             => $result->isIsento,
                'days'                  => $result->days,
                'business_days'         => $result->businessDays,
            ]);

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
