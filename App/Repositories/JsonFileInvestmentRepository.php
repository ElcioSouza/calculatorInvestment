<?php
namespace App\Repositories;

use App\Contracts\InvestmentRepositoryInterface;
use App\ValueObjects\Investment;
use App\ValueObjects\InvestmentInput;

class JsonFileInvestmentRepository implements InvestmentRepositoryInterface
{
    private string $filePath;
    private array $storage = [];
    private int $nextId = 1;

    public function __construct(?string $filePath = null)
    {
        $this->filePath = $filePath ?? dirname(__DIR__, 2) . '/data/investments.json';
        $this->loadFromFile();
    }

    public function save(InvestmentInput $input, Investment $result, ?int $id = null): int
    {
        $id = $id ?? $this->nextId++;
        $this->storage[$id] = [
            'id'     => $id,
            'input'  => $this->inputToArray($input),
            'result' => $this->resultToArray($result),
        ];
        $this->saveToFile();
        return $id;
    }

    public function all(): array
    {
        $result = [];
        foreach ($this->storage as $item) {
            $result[] = [
                'id'     => $item['id'],
                'input'  => $this->arrayToInput($item['input']),
                'result' => $this->arrayToResult($item['result']),
            ];
        }
        return $result;
    }

    public function getLast(): ?array
    {
        $all = $this->all();
        if (empty($all)) {
            return null;
        }
        return end($all);
    }

    public function findById(int|string $id): ?array
    {
        $id = (int) $id;
        if (!isset($this->storage[$id])) {
            return null;
        }
        $item = $this->storage[$id];
        return [
            'id'     => $item['id'],
            'input'  => $this->arrayToInput($item['input']),
            'result' => $this->arrayToResult($item['result']),
        ];
    }

    public function update(int|string $id, InvestmentInput $input, Investment $result): int
    {
        $id = (int) $id;
        $this->storage[$id] = [
            'id'     => $id,
            'input'  => $this->inputToArray($input),
            'result' => $this->resultToArray($result),
        ];
        $this->saveToFile();
        return $id;
    }

    public function delete(int|string $id): bool
    {
        $id = (int) $id;
        if (!isset($this->storage[$id])) {
            return false;
        }
        unset($this->storage[$id]);
        $this->saveToFile();
        return true;
    }

    private function loadFromFile(): void
    {
        if (!file_exists($this->filePath)) {
            $this->storage = [];
            $this->nextId = 1;
            return;
        }

        $content = file_get_contents($this->filePath);
        if ($content === false || empty($content)) {
            $this->storage = [];
            $this->nextId = 1;
            return;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            $this->storage = [];
            $this->nextId = 1;
            return;
        }

        $this->storage = $data['storage'] ?? [];
        $storedNextId = $data['nextId'] ?? 1;
        $maxKey = !empty($this->storage) ? max(array_keys($this->storage)) : 0;
        $this->nextId = max($storedNextId, $maxKey + 1);
    }

    private function saveToFile(): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }

        $data = [
            'nextId'  => $this->nextId,
            'storage' => $this->storage,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT);
        $tmp = $this->filePath . '.tmp.' . getmypid();
        file_put_contents($tmp, $json, LOCK_EX);
        rename($tmp, $this->filePath);
    }

    private function inputToArray(InvestmentInput $input): array
    {
        return [
            'initialCapital'       => $input->initialCapital,
            'investmentType'       => $input->investmentType,
            'rateType'             => $input->rateType,
            'cdiPercentage'        => $input->cdiPercentage,
            'selicMeta'            => $input->selicMeta,
            'preFixedAnnualRate'   => $input->preFixedAnnualRate,
            'applicationDate'      => $input->applicationDate,
            'redemptionDate'       => $input->redemptionDate,
            'months'               => $input->months,
            'selicIsOver'          => $input->selicIsOver,
            'cdiOver'              => $input->cdiOver,
            'selicMetaDefault'     => $input->selicMetaDefault,
        ];
    }

    private function resultToArray(Investment $result): array
    {
        return [
            'amountBruto'          => $result->amountBruto,
            'amountLiquid'         => $result->amountLiquid,
            'profitBruto'          => $result->profitBruto,
            'profitLiquid'         => $result->profitLiquid,
            'iofValue'             => $result->iofValue,
            'irTaxAmount'          => $result->irTaxAmount,
            'monthlyProfitLiquid'  => $result->monthlyProfitLiquid,
            'dailyProfitDisplay'   => $result->dailyProfitDisplay,
            'isIsento'             => $result->isIsento,
            'days'                 => $result->days,
            'businessDays'         => $result->businessDays,
            'irAliquot'            => $result->irAliquot,
            'profitPercentage'     => $result->profitPercentage,
        ];
    }

    private function arrayToInput(array $arr): InvestmentInput
    {
        return new InvestmentInput(
            initialCapital:       $arr['initialCapital'] ?? '0',
            investmentType:       $arr['investmentType'] ?? 'cdb',
            rateType:             $arr['rateType'] ?? 'pos',
            cdiPercentage:        $arr['cdiPercentage'] ?? '0',
            selicMeta:            $arr['selicMeta'] ?? '0',
            preFixedAnnualRate:   $arr['preFixedAnnualRate'] ?? '0',
            applicationDate:      $arr['applicationDate'] ?? date('Y-m-d'),
            redemptionDate:       $arr['redemptionDate'] ?? date('Y-m-d'),
            months:               $arr['months'] ?? 1,
            selicIsOver:          $arr['selicIsOver'] ?? false,
            cdiOver:              $arr['cdiOver'] ?? '',
            selicMetaDefault:     $arr['selicMetaDefault'] ?? '',
        );
    }

    private function arrayToResult(array $arr): Investment
    {
        return new Investment(
            amountBruto:          $arr['amountBruto'] ?? '0',
            amountLiquid:         $arr['amountLiquid'] ?? '0',
            profitBruto:          $arr['profitBruto'] ?? '0',
            profitLiquid:         $arr['profitLiquid'] ?? '0',
            iofValue:             $arr['iofValue'] ?? '0',
            irTaxAmount:          $arr['irTaxAmount'] ?? '0',
            monthlyProfitLiquid:  $arr['monthlyProfitLiquid'] ?? '0',
            dailyProfitDisplay:   $arr['dailyProfitDisplay'] ?? '0',
            isIsento:             $arr['isIsento'] ?? false,
            days:                 $arr['days'] ?? 0,
            businessDays:         $arr['businessDays'] ?? 0,
            irAliquot:            $arr['irAliquot'] ?? '0',
            profitPercentage:     $arr['profitPercentage'] ?? '0',
        );
    }
}
