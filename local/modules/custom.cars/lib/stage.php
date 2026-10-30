<?php

namespace Custom\Cars;

final class Stage
{
    public const NEW = 'NEW';
    public const OPERATION = 'OPERATION';
    public const REPAIR = 'REPAIR';
    public const SOLD = 'SOLD';

    public const NAMES = [
        self::NEW => 'New',
        self::OPERATION => 'In Operation',
        self::REPAIR => 'In Repair',
        self::SOLD => 'Sold',
    ];
}
