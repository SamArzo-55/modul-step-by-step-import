<?php

namespace Custom\Cars\Config;

use Bitrix\Main\Config\Option;

final class Car
{
    public const MODULE_ID = 'custom.cars';

    public const FIELDS = [
        'PLATE' => ['string', 'License Plate Number', ['номерной знак']],
        'BRAND' => ['string', 'Brand', ['марка']],
        'MODEL' => ['string', 'Model', ['модель']],
        'VIN' => ['string', 'VIN code', ['вин код']],
        'YEAR' => ['integer', 'Year of Manufacture', ['год выпуска']],
        'FUEL' => ['enumeration', 'Fuel Type', ['тип топлива']],
        'CUSTOMS_CLEARED' => ['boolean', 'Customs Cleared', ['растаможен']],
        'CUSTOMS_DATE' => ['datetime', 'Date of Customs Clearance', ['дата растаможки']],
        'REG_DATE' => ['datetime', 'Registration date', ['дата регистрации']],

        'ACCOUNT_ID' => ['string', 'Account ID of the Car', ['ид аккаунта автомобиля']],
    ];

    public static function save(int $entityTypeId, string $userFieldEntityId): void
    {
        Option::set(self::MODULE_ID, 'entity_type_id', $entityTypeId);
        Option::set(self::MODULE_ID, 'user_field_entity_id', $userFieldEntityId);
    }

    public static function entityTypeId(): int
    {
        return (int) Option::get(self::MODULE_ID, 'entity_type_id', 0);
    }

    public static function userFieldEntityId(): string
    {
        return Option::get(self::MODULE_ID, 'user_field_entity_id', '');
    }

    public static function fieldName(string $code): string
    {
        return 'UF_' . self::userFieldEntityId() . '_' . $code;
    }

    public static function label(string $code): string
    {
        return self::FIELDS[$code][1] ?? $code;
    }

    public static function userFieldType(string $code): string
    {
        return self::FIELDS[$code][0];
    }

    public static function codes(): array
    {
        return array_keys(self::FIELDS);
    }

    public static function headers(string $code): array
    {
        return array_merge([self::label($code)], self::FIELDS[$code][2]);
    }
}
