<?php
namespace Custom\Cars\Handler;

use Bitrix\Crm\Item;
use Bitrix\Main\Error;
use Bitrix\Main\Event;
use Bitrix\Main\EventResult;
use Bitrix\Main\Result;
use Custom\Cars\Config\Car;
use Custom\Cars\Service\DeletionNotifier;
use Custom\Cars\Service\RegistrationDateFiller;
use Custom\Cars\Service\Validation;
use Custom\Cars\Service\VinNormalizer;

final class ItemEvents
{
    public static function onBeforeAdd(Event $event): ?EventResult
    {
        $item = self::carItem($event);

        if ($item === null) {
            return null;
        }

        self::normalize($item);
        (new RegistrationDateFiller())->fill($item);

        return self::toEventResult((new Validation())->validate($item));
    }

    public static function onBeforeUpdate(Event $event): ?EventResult
    {
        $item = self::carItem($event);

        if ($item === null) {
            return null;
        }

        self::normalize($item);

        return self::toEventResult((new Validation())->validate($item));
    }

    public static function onAfterAdd(Event $event): void
    {
        $item = self::carItem($event);

        if ($item !== null) {
            (new RegistrationDateFiller())->persist($item);
        }
    }

    public static function onDelete(Event $event): void
    {
        $item = self::carItem($event);

        if ($item !== null) {
            (new DeletionNotifier())->notify($item);
        }
    }

    private static function normalize(Item $item): void
    {
        $name = Car::fieldName('VIN');
        $vin = $item->get($name);

        if (is_string($vin) && $vin !== '') {
            $item->set($name, VinNormalizer::normalize($vin));
        }
    }

    private static function carItem(Event $event): ?Item
    {
        $item = $event->getParameter('item');

        return $item instanceof Item && $item->getEntityTypeId() === Car::entityTypeId() ? $item : null;
    }

    private static function toEventResult(Result $result): ?EventResult
    {
        if ($result->isSuccess()) {
            return null;
        }

        return new EventResult(EventResult::ERROR, new Error(implode(' ', $result->getErrorMessages())));
    }
}
