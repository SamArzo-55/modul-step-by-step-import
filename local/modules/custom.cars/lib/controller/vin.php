<?php

namespace Custom\Cars\Controller;

use Custom\Cars\Controller\Filter\CarsAccess;
use Custom\Cars\Repository\VinRepository;

final class Vin extends BaseController
{
    public function configureActions(): array
    {
        return [
            'isTaken' => ['+prefilters' => [new CarsAccess(CarsAccess::READ)]],
        ];
    }

    public function isTakenAction(string $vin = '', int $id = 0): array
    {
        return ['exists' => (new VinRepository())->exists($vin, $id)];
    }
}
