<?php

namespace App\Services;

use App\Core\Config;
use PDO;

class CdiRateService
{
    private CdiApiClient $apiClient;
    private CdiRateCalculator $calculator;
    private ?PDO $pdo;

    public function __construct(
        ?CdiApiClient $apiClient = null,
        ?CdiRateCalculator $calculator = null,
        ?PDO $pdo = null
    ) {
        $this->apiClient = $apiClient ?? new CdiApiClient();
        $this->calculator = $calculator ?? new CdiRateCalculator();
        $this->pdo = $pdo;
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

        $annualResult = $this->apiClient->fetchLatestRecord(
            Config::bcbCdiAnnualUrl()
        );
        if ($annualResult !== null) {
            ['valor' => $valor, 'data' => $data] = $annualResult;
            if ($this->calculator->isAnnualRateValid($valor)) {
                return [
                    'rate'   => number_format($valor, 8, '.', ''),
                    'source' => "BC/SGS série 4390 - CDI a.a. ({$data})",
                ];
            }
        }

        return $this->fallbackCdi($selicMetaFallback, $spreadFallback, 'API BC indisponível');
    }

    public function fetchSelicAnnual(): string
    {
        $result = $this->apiClient->fetchLatestRecord(
            Config::bcbSelicMetaUrl()
        );
        if ($result !== null) {
            ['valor' => $valorMeta, 'data' => $data] = $result;
            if ($this->calculator->isAnnualRateValid($valorMeta)) {
                $rate = number_format($valorMeta, 8, '.', '');
                $this->saveSelicRate($rate, $data);
                return $rate;
            }
        }

        $stored = $this->getLastStoredSelicRate();
        if ($stored !== null) {
            return $stored;
        }

        return Config::defaultSelicMeta();
    }

    public function getDisplaySelic(): string
    {
        $rate = $this->fetchSelicAnnual();
        return number_format((float) $rate, 2, '.', '');
    }

    public function fetchSelicOverAnnual(): ?string
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
        return null;
    }

    public function getSelicMetaToOverSpread(): string
    {
        $meta = (float) $this->fetchSelicAnnual();
        $over = $this->fetchSelicOverAnnual();

        if ($over !== null) {
            $spread = (float) $over - $meta;
            return number_format($spread, 8, '.', '');
        }

        return '0.15';
    }

    private function fallbackCdi(string $selicMeta, string $spread, string $reason): array
    {
        $cdiOver = number_format((float) $selicMeta + (float) $spread, 8, '.', '');

        return [
            'rate'   => $cdiOver,
            'source' => "Offline ({$reason}) — Selic Meta {$selicMeta} + spread {$spread} = {$cdiOver}% a.a.",
        ];
    }

    private function saveSelicRate(string $annualRate, string $rateDate): void
    {
        if ($this->pdo === null) {
            return;
        }

        try {
            $stmt = $this->pdo->prepare(
                'INSERT INTO selic_rates (rate_date, annual_rate) VALUES (?, ?)
                 ON DUPLICATE KEY UPDATE annual_rate = VALUES(annual_rate)'
            );
            $stmt->execute([$rateDate, $annualRate]);
        } catch (\Exception $e) {
            // DB opcional - falha silenciosa
        }
    }

    private function getLastStoredSelicRate(): ?string
    {
        if ($this->pdo === null) {
            return null;
        }

        try {
            $stmt = $this->pdo->query(
                'SELECT annual_rate FROM selic_rates ORDER BY rate_date DESC LIMIT 1'
            );
            $row = $stmt->fetch();
            return $row && $row['annual_rate'] !== null
                ? (string) $row['annual_rate']
                : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}
