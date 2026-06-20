<?php

namespace App\Services;

use App\Core\Config;

class SelicService
{
    private CdiApiClient $apiClient;
    private CdiRateCalculator $calculator;

    public function __construct(
        ?CdiApiClient $apiClient = null,
        ?CdiRateCalculator $calculator = null
    ) {
        $this->apiClient = $apiClient ?? new CdiApiClient();
        $this->calculator = $calculator ?? new CdiRateCalculator();
    }

    public function execute(): ?array
    {
        $result = $this->apiClient->fetchLatestRecord(
            Config::bcbSelicMetaUrl()
        );

        if ($result === null) {
            return null;
        }

        ['valor' => $valor, 'data' => $data] = $result;

        if (!$this->calculator->isAnnualRateValid($valor)) {
            return null;
        }

        return [
            'rate'   => number_format($valor, 2, '.', ''),
            'date'   => $data,
            'source' => 'BCB/SGS Séries Temporais',
        ];
    }
}
