<?php

namespace Custom\Cars\UI;

use Bitrix\Main\Loader;
use Bitrix\Main\Page\Asset;
use Bitrix\Main\UI\Extension;
use Bitrix\Main\Web\Json;
use Bitrix\UI\Buttons\Color;
use Bitrix\UI\Buttons\Tag;
use Bitrix\UI\Toolbar\Facade\Toolbar;
use Custom\Cars\Config\Routes;

final class Button
{
    public static function onProlog(): void
    {
        $page = new CarPage();

        if (!$page->isCarsPage()) {
            return;
        }

        Loader::requireModule('ui');
        Extension::load(['ui.notification', 'ui.forms', 'custom.cars-form']);

        self::publishFormConfig();

        if (!$page->isDetailsPage()) {
            self::addImportButton();
        }
    }

    private static function addImportButton(): void
    {
        Toolbar::addButton([
            'tag' => Tag::LINK,
            'link' => Routes::IMPORT_PAGE,
            'text' => 'Import from Excel',
            'color' => Color::LIGHT_BORDER,
        ]);
    }

    private static function publishFormConfig(): void
    {
        Asset::getInstance()->addString('<script>window.CustomCarsConfig = ' . Json::encode((new FormConfig())->build()) . ';</script>');
    }
}
