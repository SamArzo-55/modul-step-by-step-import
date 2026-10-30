<?php

namespace Custom\Cars\UI;

use Custom\Cars\Config\Car;
use Custom\Cars\Config\Routes;
use Custom\Cars\FieldRules;
use Custom\Cars\Service\Validation;
use Custom\Cars\Stage;

final class FormConfig
{
    public function build(): array
    {
        $fields = [];
        $labels = [];

        foreach (Car::codes() as $code) {
            $fields[$code] = Car::fieldName($code);
            $labels[$code] = Car::label($code);
        }

        return [
            'entityTypeId' => Car::entityTypeId(),
            'fields' => $fields,
            'labels' => $labels,
            'required' => Validation::REQUIRED,
            'firstYear' => Validation::FIRST_CAR_YEAR,
            'currentYear' => (int) date('Y'),
            'stages' => FieldRules::forJs(),
            'stageNames' => Stage::NAMES,
            'api' => [
                'vinExists' => Routes::VIN_EXISTS,
            ],
        ];
    }
}
