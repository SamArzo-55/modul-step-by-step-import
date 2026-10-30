<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

return [
    'js' => './dist/cars-import.bundle.js',
    'rel' => [
        'main.core',
        'ui.stepprocessing',
    ],
    'skip_core' => false,
];
