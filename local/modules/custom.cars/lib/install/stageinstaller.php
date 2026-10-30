<?php

namespace Custom\Cars\Install;

use Bitrix\Crm\Service\Container;
use Bitrix\Crm\StatusTable;
use Bitrix\Main\Application;
use Custom\Cars\Config\Car;
use Custom\Cars\Stage;
use RuntimeException;
use Throwable;

final class StageInstaller
{
    public function install(): void
    {
        $entityTypeId = Car::entityTypeId();
        $factory = Container::getInstance()->getFactory($entityTypeId);

        if ($factory === null) {
            throw new RuntimeException('Car smart process is not available.');
        }

        $categoryId = $factory->getDefaultCategory()->getId();
        $statusEntityId = $factory->getStagesEntityId($categoryId);
        $connection = Application::getConnection();

        $connection->startTransaction();

        try {
            $this->deleteStages($statusEntityId);

            $sort = 0;

            foreach (Stage::NAMES as $code => $name) {
                $result = StatusTable::add([
                    'ENTITY_ID' => $statusEntityId,
                    'STATUS_ID' => 'DT' . $entityTypeId . '_' . $categoryId . ':' . $code,
                    'NAME' => $name,
                    'SORT' => $sort += 10,
                    'SEMANTICS' => $code === Stage::SOLD ? 'S' : null,
                ]);

                if (!$result->isSuccess()) {
                    throw new RuntimeException('Cannot create stage "' . $name . '": ' . implode('; ', $result->getErrorMessages()));
                }
            }

            $connection->commitTransaction();
        } catch (Throwable $e) {
            $connection->rollbackTransaction();

            throw $e;
        }
    }

    private function deleteStages(string $statusEntityId): void
    {
        $stages = StatusTable::getList(['select' => ['ID'], 'filter' => ['=ENTITY_ID' => $statusEntityId]]);

        while ($stage = $stages->fetch()) {
            StatusTable::delete($stage['ID']);
        }
    }
}
