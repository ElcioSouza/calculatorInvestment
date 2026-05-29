<?php

namespace App\Services;

use App\Core\Config;

class CdiRateService
{
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
        
        $dailyResult = $this->apiClient->fetchLatestRecord(
            Config::bcbCdiDailyUrl()
        );
        if ($dailyResult !== null) {
            ['valor' => $valorDiario, 'data' => $data] = $dailyResult;
            if ($this->calculator->isDailyRateValid($valorDiario)) {
                $annual = $this->calculator->annualizeDailyRate($valorDiario);
                return [
                    'rate'   => number_format($annual, 8, '.', ''),
                    'source' => sprintf('BC/SGS série 12 - CDI diário %.6f%% → a.a. base 252 (%s)', $valorDiario, $data),
                ];
            }
        }
        
        $monthlyResult = $this->apiClient->fetchLatestRecord(
            Config::bcbCdiAnnualUrl()
        );
        if ($monthlyResult !== null) {
            ['valor' => $valor, 'data' => $data] = $monthlyResult;
            if ($valor > 0.0 && $valor < 100.0) {
                if ($valor > 5.0) {
                    
                    return [
                        'rate'   => number_format($valor, 8, '.', ''),
                        'source' => "BC/SGS série 4390 - CDI a.a. ({$data})",
                    ];
                }
                
                $annual = (pow(1.0 + $valor / 100.0, 12) - 1.0) * 100.0;
                return [
                    'rate'   => number_format($annual, 8, '.', ''),
                    'source' => sprintf(
                        'BC/SGS série 4390 - CDI mensal %.4f%% → a.a. (%s)',
                        $valor,
                        $data
                    ),
                ];
            }
        }

        return $this->fallback($selicMetaFallback, $spreadFallback, 'API BC indisponível');
    }

    public function fetchSelicAnnual(?string $fallback = null): ?string
    {
        $result = $this->apiClient->fetchLatestRecord(
            Config::bcbSelicDailyUrl()
        );
        if ($result !== null) {
            ['valor' => $valorDiario] = $result;
            if ($this->calculator->isDailyRateValid($valorDiario)) {
                $annual = $this->calculator->annualizeDailyRate($valorDiario);
                return number_format($annual, 8, '.', '');
            }
        }
        return $fallback;
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