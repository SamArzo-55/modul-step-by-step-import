<?php

namespace Custom\Cars\Import;

use Custom\Cars\Config\Car;
use Custom\Cars\Service\VinNormalizer;

final class ValueConverter
{
    private const TRUE_VALUES = ['1', 'y', 'yes', 'true', 'да'];

    private FuelResolver $fuel;
    private DateParser $dates;

    public function __construct(?FuelResolver $fuel = null, ?DateParser $dates = null)
    {
        $this->fuel = $fuel ?? new FuelResolver();
        $this->dates = $dates ?? new DateParser();
    }

    public function convert(string $code, string $value): array
    {
        $label = Car::label($code);

        return match ($code) {
            'FUEL' => $this->fuel($label, $value),
            'CUSTOMS_CLEARED' => [(int) in_array(mb_strtolower($value), self::TRUE_VALUES, true), null],
            'CUSTOMS_DATE', 'REG_DATE' => $this->date($label, $value),
            'YEAR' => [is_numeric($value) ? (int) round((float) $value) : $value, null],
            'VIN' => [VinNormalizer::normalize($value), null],
            default => [$value, null],
        };
    }

    private function fuel(string $label, string $value): array
    {
        $id = $this->fuel->resolve($value);

        return $id === null ? [null, $label . ': unknown value "' . $value . '".'] : [$id, null];
    }

    private function date(string $label, string $value): array
    {
        $date = $this->dates->parse($value);

        return $date === null ? [null, $label . ': cannot recognize the date "' . $value . '".'] : [$date, null];
    }
}
