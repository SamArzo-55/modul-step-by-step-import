<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Web\Json;

$arResult['CONFIG_JSON'] = Json::encode($arResult['CONFIG']);
$arResult['BACK_URL'] = $arResult['CONFIG']['backUrl'] ?? '/';
