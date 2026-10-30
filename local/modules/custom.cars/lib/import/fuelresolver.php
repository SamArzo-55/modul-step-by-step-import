<?php

namespace Custom\Cars\Import;

use CUserFieldEnum;
use Custom\Cars\Config\Car;

final class FuelResolver
{
    private const ALIASES = [
        'бенз' => 'gasoline',
        'gas' => 'gasoline',
        'petrol' => 'gasoline',
        'диз' => 'diesel',
        'дт' => 'diesel',
        'гибр' => 'hybrid',
        'электр' => 'electric',
    ];

    private ?array $ids = null;

    public function resolve(string $value): ?int
    {
        $value = mb_strtolower($value);
        $this->ids ??= $this->load();

        foreach (self::ALIASES as $prefix => $xmlId) {
            if (str_starts_with($value, $prefix)) {
                return $this->ids[$xmlId] ?? null;
            }
        }

        return $this->ids[$value] ?? null;
    }

    private function load(): array
    {
        $ids = [];
        $enums = CUserFieldEnum::GetList([], ['USER_FIELD_NAME' => Car::fieldName('FUEL')]);

        while ($enum = $enums->Fetch()) {
            $ids[$enum['XML_ID']] = (int) $enum['ID'];
        }

        return $ids;
    }
}
