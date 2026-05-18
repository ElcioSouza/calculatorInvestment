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

        foreach (array_reverse($data) as $entry) {
            if (!isset($entry['valor'])) {
                continue;
            }

            $valor = (float) str_replace(',', '.', trim((string) $entry['valor']));
            $date  = (string) ($entry['data'] ?? '');

            if ($valor > 0.0) {
                return ['valor' => $valor, 'data' => $date];
            }
        }

        return null;
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