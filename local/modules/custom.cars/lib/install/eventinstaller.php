<?php
namespace Custom\Cars\Install;

use Bitrix\Main\EventManager;
use Custom\Cars\Config\Car;
use Custom\Cars\Handler\ItemEvents;
use Custom\Cars\UI\Button;

final class EventInstaller
{
    private const HANDLERS = [
        ['main', 'OnProlog', Button::class, 'onProlog'],

        ['crm', 'onBeforeCrmDynamicItemAdd', ItemEvents::class, 'onBeforeAdd'],
        ['crm', 'onBeforeCrmDynamicItemUpdate', ItemEvents::class, 'onBeforeUpdate'],
        ['crm', 'onCrmDynamicItemAdd', ItemEvents::class, 'onAfterAdd'],
        ['crm', 'onCrmDynamicItemDelete', ItemEvents::class, 'onDelete'],
    ];

    public function install(): void
    {
        $manager = EventManager::getInstance();

        foreach (self::HANDLERS as [$module, $event, $class, $method]) {
            $manager->unRegisterEventHandler($module, $event, Car::MODULE_ID, $class, $method);
            $manager->registerEventHandler($module, $event, Car::MODULE_ID, $class, $method);
        }
    }

    public function uninstall(): void
    {
        $manager = EventManager::getInstance();

        foreach (self::HANDLERS as [$module, $event, $class, $method]) {
            $manager->unRegisterEventHandler($module, $event, Car::MODULE_ID, $class, $method);
        }
    }
}
