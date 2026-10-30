<?php

namespace Custom\Cars\Config;

final class Routes
{
    public const VIN_EXISTS = 'custom:cars.vin.isTaken';

    public const IMPORT_CONTROLLER = 'custom:cars.import';

    public const IMPORT_PAGE = '/cars_import/';

    public static function listUrl(): string
    {
        return '/crm/type/' . Car::entityTypeId() . '/';
    }
}
