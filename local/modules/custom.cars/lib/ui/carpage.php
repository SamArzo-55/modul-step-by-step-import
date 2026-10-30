<?php

namespace Custom\Cars\UI;

use Bitrix\Main\Context;
use Custom\Cars\Config\Car;

final class CarPage
{
    public function isCarsPage(): bool
    {
        $entityTypeId = Car::entityTypeId();

        return $entityTypeId > 0 && str_starts_with($this->uri(), '/crm/type/' . $entityTypeId . '/');
    }

    public function isDetailsPage(): bool
    {
        return str_contains($this->uri(), '/details/');
    }

    private function uri(): string
    {
        return (string) Context::getCurrent()->getRequest()->getRequestUri();
    }
}
