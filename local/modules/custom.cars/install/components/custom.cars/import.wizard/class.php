<?php

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\UI\Extension;
use Custom\Cars\Config\Routes;
use Custom\Cars\Service\AccessChecker;

class CustomCarsImportWizardComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        Loader::requireModule('crm');
        Loader::requireModule('custom.cars');

        $this->arResult = ['ERROR' => null, 'CONFIG' => []];

        if ((new AccessChecker())->canAdd()) {
            Extension::load(['ui.buttons', 'ui.stepprocessing', 'custom.cars-import']);

            $this->arResult['CONFIG'] = [
                'controller' => Routes::IMPORT_CONTROLLER,
                'backUrl' => Routes::listUrl(),
            ];
        } else {
            $this->arResult['ERROR'] = 'Access denied.';
        }

        $this->includeComponentTemplate();
    }
}
