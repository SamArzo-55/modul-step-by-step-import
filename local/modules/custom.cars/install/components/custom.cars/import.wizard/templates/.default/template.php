<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

if ($arResult['ERROR'] !== null) {
    ShowError(htmlspecialcharsbx($arResult['ERROR']));

    return;
}
?>
<div class="cars-import" data-cars-import data-config="<?= htmlspecialcharsbx($arResult['CONFIG_JSON']) ?>">
    <p>Import cars from an .xlsx file. The column titles must be in the first row.</p>
    <button class="ui-btn ui-btn-primary" type="button" data-role="open">Import from Excel</button>
    <a class="ui-btn ui-btn-light-border" href="<?= htmlspecialcharsbx($arResult['BACK_URL']) ?>">Back to cars</a>
</div>
