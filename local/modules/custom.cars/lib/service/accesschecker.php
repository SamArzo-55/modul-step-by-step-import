<?php

namespace Custom\Cars\Service;

use Bitrix\Crm\Service\Container;
use Bitrix\Main\Loader;
use Custom\Cars\Config\Car;

final class AccessChecker
{
    public function canRead(): bool
    {
        return $this->isAvailable() && Container::getInstance()->getUserPermissions()->checkReadPermissions(Car::entityTypeId());
    }

    public function canAdd(): bool
    {
        return $this->isAvailable() && Container::getInstance()->getUserPermissions()->checkAddPermissions(Car::entityTypeId());
    }

    private function isAvailable(): bool
    {
        return Loader::includeModule('crm') && Car::entityTypeId() > 0;
    }
}
