<?php

namespace App\Services;

class CdiRateService
{
    private const SGS_CDI_ANNUAL_URL =
        'https://api.bcb.gov.br/dados/serie/bcdata.sgs.4390/dados/ultimos/5?formato=json';

    private const SGS_CDI_DAILY_URL =
        'https://api.bcb.gov.br/dados/serie/bcdata.sgs.12/dados/ultimos/5?formato=json';

    private CdiApiClient $apiClient;
    private CdiRateCalculator $calculator;

    public function __construct(?CdiApiClient $apiClient = null, ?CdiRateCalculator $calculator = null)
    {
        $this->apiClient = $apiClient ?? new CdiApiClient();
        $this->calculator = $calculator ?? new CdiRateCalculator();
    }

    public function fetchCdiAnnual(
        string $selicMetaFallback,
        string $spreadFallback = '-0.10'
    ): array {
        $result = $this->apiClient->fetchLatestRecord(self::SGS_CDI_ANNUAL_URL);
        if ($result !== null) {
            ['valor' => $valor, 'data' => $data] = $result;
            if ($this->calculator->isAnnualRateValid($valor)) {
                return [
                    'rate'   => number_format($valor, 8, '.', ''),
                    'source' => "BC/SGS série 4390 - CDI a.a. ({$data})",
                ];
            }
        }

        $result = $this->apiClient->fetchLatestRecord(self::SGS_CDI_DAILY_URL);
        if ($result !== null) {
            ['valor' => $valorDiario, 'data' => $data] = $result;
            if ($this->calculator->isDailyRateValid($valorDiario)) {
                $annual = $this->calculator->annualizeDailyRate($valorDiario);
                return [
                    'rate'   => number_format($annual, 8, '.', ''),
                    'source' => sprintf(
                        'BC/SGS série 12 - CDI diário %.6f%% → a.a. base 252 (%s)',
                        $valorDiario,
                        $data
                    ),
                ];
            }
        }

        return $this->fallback($selicMetaFallback, $spreadFallback, 'API BC indisponível');
    }

    private function fallback(string $selicMeta, string $spread, string $reason): array
    {
        $cdiOver = number_format((float) $selicMeta + (float) $spread, 8, '.', '');

        return [
            'rate'   => $cdiOver,
            'source' => "Offline ({$reason}) — Selic Meta {$selicMeta} + spread {$spread} = {$cdiOver}% a.a.",
        ];
    }
}