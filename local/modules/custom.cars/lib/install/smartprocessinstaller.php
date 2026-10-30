<?php

namespace Custom\Cars\Install;

use Bitrix\Crm\Service\Container;
use Custom\Cars\Config\Car;
use RuntimeException;

final class SmartProcessInstaller
{
    private const ITEMS_PER_PASS = 1000;

    private const LEAN_SWITCHES = [
        'disableCheckAccess',
        'disableCheckFields',
        'disableCheckWorkflows',
        'disableBizProc',
        'disableAutomation',
    ];

    public function create(): int
    {
        $container = Container::getInstance();
        $dataClass = $container->getDynamicTypeDataClass();

        $type = $dataClass::createObject();
        $type->set('TITLE', 'Car Management')
            ->set('NAME', 'CarManagement')
            ->set('IS_USE_IN_USERFIELD_ENABLED', 'Y')
            ->set('IS_CLIENT_ENABLED', 'Y')
            ->set('IS_STAGES_ENABLED', 'Y')
            ->set('IS_CATEGORIES_ENABLED', 'Y');

        $result = $type->save();

        if (!$result->isSuccess()) {
            throw new RuntimeException('Cannot create the smart process: ' . implode('; ', $result->getErrorMessages()));
        }

        $entityTypeId = (int) $type->getEntityTypeId();
        $factory = $container->getFactory($entityTypeId);

        if ($entityTypeId <= 0 || $factory === null) {
            throw new RuntimeException('The smart process was created but its factory is not available.');
        }

        Car::save($entityTypeId, $factory->getUserFieldEntityId());

        return $entityTypeId;
    }

    public function deleteItems(): void
    {
        $factory = Car::entityTypeId() > 0 ? Container::getInstance()->getFactory(Car::entityTypeId()) : null;

        if ($factory === null) {
            return;
        }

        do {
            $items = $factory->getItems(['select' => ['ID'], 'limit' => self::ITEMS_PER_PASS]);
            $deleted = 0;

            foreach ($items as $item) {
                $operation = $factory->getDeleteOperation($item);

                foreach (self::LEAN_SWITCHES as $switch) {
                    if (method_exists($operation, $switch)) {
                        $operation->$switch();
                    }
                }

                if ($operation->launch()->isSuccess()) {
                    $deleted++;
                }
            }
        } while ($items && $deleted > 0);
    }

    public function delete(): void
    {
        $type = Car::entityTypeId() > 0 ? Container::getInstance()->getTypeByEntityTypeId(Car::entityTypeId()) : null;

        if ($type === null) {
            return;
        }

        $result = $type->delete();

        if (!$result->isSuccess()) {
            throw new RuntimeException('Cannot delete the smart process: ' . implode('; ', $result->getErrorMessages()));
        }
    }
}
