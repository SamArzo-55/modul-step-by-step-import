<?php

namespace Custom\Cars\Service;

use Bitrix\Crm\Item;
use Bitrix\Main\Error;
use Bitrix\Main\Result;
use Custom\Cars\Config\Car;
use Custom\Cars\Repository\VinRepository;

final class Validation
{
    public const FIRST_CAR_YEAR = 1886;

    public const REQUIRED = ['BRAND', 'MODEL', 'PLATE'];

    public const CHECKED = ['BRAND', 'MODEL', 'PLATE', 'VIN', 'YEAR'];

    private VinRepository $vins;

    public function __construct(?VinRepository $vins = null)
    {
        $this->vins = $vins ?? new VinRepository();
    }

    public function validate(Item $item): Result
    {
        $values = [];

        foreach (self::CHECKED as $code) {
            $values[$code] = $item->get(Car::fieldName($code));
        }

        return $this->validateValues($values, (int) $item->getId());
    }

    public function validateValues(array $values, int $itemId): Result
    {
        $result = new Result();

        foreach (self::REQUIRED as $code) {
            if (trim((string) ($values[$code] ?? '')) === '') {
                $result->addError(new Error(Car::label($code) . ' is required.'));
            }
        }

        $vin = VinNormalizer::normalize((string) ($values['VIN'] ?? ''));

        if ($this->vins->exists($vin, $itemId)) {
            $result->addError(new Error('A car with VIN code "' . $vin . '" already exists.'));
        }

        $yearError = $this->yearError($values['YEAR'] ?? null);

        if ($yearError !== null) {
            $result->addError(new Error($yearError));
        }

        return $result;
    }

    private function yearError(mixed $value): ?string
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $label = Car::label('YEAR');
        $year = filter_var(trim((string) $value), FILTER_VALIDATE_INT);

        if ($year === false) {
            return $label . ' must be a whole number, for example 2018.';
        }

        $current = (int) date('Y');

        if ($year < self::FIRST_CAR_YEAR) {
            return $label . ' cannot be earlier than ' . self::FIRST_CAR_YEAR . ', the year the first car was built.';
        }

        if ($year > $current) {
            return $label . ' cannot be later than the current year (' . $current . ').';
        }

        return null;
    }
}
