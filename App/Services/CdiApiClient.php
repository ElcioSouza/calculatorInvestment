<?php

namespace App\Services;

class CdiApiClient
{
    private const TIMEOUT_SECS = 5;

    public function fetchLatestRecord(string $url): ?array
    {
        $raw = $this->fetchUrl($url);
        if ($raw === null) {
            return null;
        }

        $data = json_decode($raw, true);
        if (!is_array($data) || empty($data)) {
            return null;
        }

        $latestEntry = null;
        $latestDate = null;

        foreach ($data as $entry) {
            if (!isset($entry['valor'], $entry['data'])) {
                continue;
            }

            $date = $this->parseDate((string) $entry['data']);
            if ($date === null) {
                continue;
            }

            if ($latestDate === null || $date > $latestDate) {
                $latestDate = $date;
                $latestEntry = $entry;
            }
        }

        if ($latestEntry === null) {
            return null;
        }

        $valor = (float) str_replace(',', '.', trim((string) $latestEntry['valor']));
        if ($valor <= 0.0) {
            return null;
        }

        return [
            'valor' => $valor,
            'data' => (string) $latestEntry['data'],
        ];
    }

    private function parseDate(string $date): ?\DateTimeImmutable
    {
        $parsed = \DateTimeImmutable::createFromFormat('d/m/Y', $date);

        return $parsed instanceof \DateTimeImmutable ? $parsed : null;
    }

    private function fetchUrl(string $url): ?string
    {
        if (!function_exists('curl_init')) {
            return null;
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECS,
            CURLOPT_CONNECTTIMEOUT => self::TIMEOUT_SECS,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: InvestmentCalculator/1.0',
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);
        curl_close($curl);

        if ($response === false || $httpCode !== 200 || strlen($response) <= 2) {
            if ($curlError) {
                error_log("[CdiApiClient] cURL erro em {$url}: {$curlError}");
            } elseif ($httpCode !== 200) {
                error_log("[CdiApiClient] HTTP {$httpCode} em {$url}");
            }

            return null;
        }

        return $response;
    }
}