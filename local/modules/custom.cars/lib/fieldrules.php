<?php

namespace Custom\Cars;

use Custom\Cars\Config\Car;

final class FieldRules
{
    public static function locked(string $stageCode): array
    {
        $codes = [];

        if ($stageCode === Stage::OPERATION) {
            $codes = ['VIN', 'PLATE'];
        }

        if ($stageCode !== Stage::NEW) {
            $codes[] = 'REG_DATE';
        }

        return $codes;
    }

    public static function hidden(string $stageCode): array
    {
        return $stageCode === Stage::OPERATION ? ['YEAR', 'FUEL'] : [];
    }

    public static function forJs(): array
    {
        $map = static fn (array $codes): array => array_map([Car::class, 'fieldName'], array_values($codes));
        $rules = [];

        foreach (array_keys(Stage::NAMES) as $stageCode) {
            $rules[$stageCode] = [
                'locked' => $map(self::locked($stageCode)),
                'hidden' => $map(self::hidden($stageCode)),
            ];
        }

        return $rules;
    }
}
