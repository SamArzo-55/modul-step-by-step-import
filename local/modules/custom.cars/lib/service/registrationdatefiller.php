<?php
namespace Custom\Cars\Service;

use Bitrix\Crm\Item;
use Bitrix\Crm\Service\Container;
use Bitrix\Main\Type\DateTime;
use Custom\Cars\Config\Car;
use DateTimeInterface;

final class RegistrationDateFiller
{
    public function isEmpty(Item $item): bool
    {
        $value = $item->get(Car::fieldName('REG_DATE'));

        if ($value instanceof DateTimeInterface) {
            return false;
        }

        return $value === null || $value === false || trim((string) $value) === '';
    }

    public function fill(Item $item): bool
    {
        if (!$this->isEmpty($item)) {
            return false;
        }

        $item->set(Car::fieldName('REG_DATE'), new DateTime());

        return true;
    }

    public function persist(Item $item): void
    {
        if ($item->getId() <= 0 || !$this->isEmpty($item)) {
            return;
        }

        $dataClass = Container::getInstance()->getFactory(Car::entityTypeId())->getDataClass();
        $dataClass::update($item->getId(), [Car::fieldName('REG_DATE') => new DateTime()]);
    }
}
