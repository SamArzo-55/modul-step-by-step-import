<?php

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/header.php';

global $APPLICATION;

$APPLICATION->SetTitle('Import cars from Excel');
$APPLICATION->IncludeComponent('custom.cars:import.wizard', '', []);

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/footer.php';
