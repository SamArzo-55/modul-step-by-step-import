<?php

namespace Custom\Cars\Repository;

use Bitrix\Crm\Service\Container;
use Custom\Cars\Config\Car;
use Custom\Cars\Config\ImportSettings;
use Custom\Cars\Service\VinNormalizer;

final class VinRepository
{
    private array $known = [];

    private array $checked = [];

    public function exists(string $vin, int $excludeId = 0): bool
    {
        $vin = VinNormalizer::normalize($vin);

        if ($vin === '') {
            return false;
        }

        if ($excludeId <= 0 && isset($this->checked[$vin])) {
            return isset($this->known[$vin]);
        }

        $filter = ['=' . Car::fieldName('VIN') => $vin];

        if ($excludeId > 0) {
            $filter['!=ID'] = $excludeId;
        }

        return $this->dataClass()::getCount($filter) > 0;
    }

    public function findExisting(array $vins): array
    {
        $vins = array_values(array_unique(array_filter(array_map([VinNormalizer::class, 'normalize'], $vins), 'strlen')));
        $field = Car::fieldName('VIN');
        $dataClass = $this->dataClass();
        $found = [];

        foreach (array_chunk($vins, ImportSettings::LOOKUP_CHUNK) as $chunk) {
            $rows = $dataClass::getList(['select' => ['ID', $field], 'filter' => ['@' . $field => $chunk]]);

            while ($row = $rows->fetch()) {
                $found[VinNormalizer::normalize((string) $row[$field])] = true;
            }
        }

        return $found;
    }

    public function preload(array $vins): void
    {
        $vins = array_filter(array_map([VinNormalizer::class, 'normalize'], $vins), 'strlen');
        $vins = array_values(array_diff(array_unique($vins), array_keys($this->checked)));

        if (!$vins) {
            return;
        }

        $existing = $this->findExisting($vins);

        foreach ($vins as $vin) {
            $this->checked[$vin] = true;

            if (isset($existing[$vin])) {
                $this->known[$vin] = true;
            }
        }
    }

    public function remember(string $vin): void
    {
        $vin = VinNormalizer::normalize($vin);

        if ($vin !== '') {
            $this->known[$vin] = true;
            $this->checked[$vin] = true;
        }
    }

    private function dataClass(): string
    {
        return Container::getInstance()->getFactory(Car::entityTypeId())->getDataClass();
    }
}
