<?php

namespace Custom\Cars\Install;

use CUserFieldEnum;
use CUserTypeEntity;
use Custom\Cars\Config\Car;
use Custom\Cars\Service\Validation;
use RuntimeException;

final class FieldInstaller
{
    private const FUEL_TYPES = [
        'gasoline' => 'Gasoline',
        'diesel' => 'Diesel',
        'hybrid' => 'Hybrid',
        'electric' => 'Electric',
    ];

    public function install(): void
    {
        foreach (Car::codes() as $code) {
            $this->addField($code);
        }
    }

    public function uninstall(): void
    {
        $entityId = Car::userFieldEntityId();

        if ($entityId === '') {
            return;
        }

        $names = array_map([Car::class, 'fieldName'], Car::codes());
        $fields = CUserTypeEntity::GetList([], ['ENTITY_ID' => $entityId]);
        $entity = new CUserTypeEntity();

        while ($field = $fields->Fetch()) {
            if (in_array($field['FIELD_NAME'], $names, true)) {
                $entity->Delete($field['ID']);
            }
        }
    }

    private function addField(string $code): void
    {
        $label = Car::label($code);
        $labels = ['en' => $label, 'ru' => $label];
        $sort = 100 + 10 * (array_search($code, Car::codes(), true) + 1);

        $id = (new CUserTypeEntity())->Add([
            'ENTITY_ID' => Car::userFieldEntityId(),
            'FIELD_NAME' => Car::fieldName($code),
            'USER_TYPE_ID' => Car::userFieldType($code),
            'XML_ID' => $code,
            'SORT' => $sort,
            'MULTIPLE' => 'N',
            'MANDATORY' => in_array($code, Validation::REQUIRED, true) ? 'Y' : 'N',
            'SHOW_FILTER' => 'S',
            'SHOW_IN_LIST' => 'Y',
            'EDIT_IN_LIST' => 'Y',
            'EDIT_FORM_LABEL' => $labels,
            'LIST_COLUMN_LABEL' => $labels,
            'LIST_FILTER_LABEL' => $labels,
        ]);

        if (!$id) {
            global $APPLICATION;

            $exception = $APPLICATION ? $APPLICATION->GetException() : null;

            throw new RuntimeException('Cannot create the field ' . $code . ($exception ? ': ' . $exception->GetString() : '.'));
        }

        if ($code === 'FUEL') {
            $this->addFuelTypes((int) $id);
        }
    }

    private function addFuelTypes(int $fieldId): void
    {
        $values = [];
        $sort = 0;

        foreach (self::FUEL_TYPES as $xmlId => $value) {
            $values['n' . $sort] = [
                'VALUE' => $value,
                'XML_ID' => $xmlId,
                'SORT' => $sort += 10,
                'DEF' => 'N',
            ];
        }

        if (!(new CUserFieldEnum())->SetEnumValues($fieldId, $values)) {
            throw new RuntimeException('Cannot create the fuel type values.');
        }
    }
}
