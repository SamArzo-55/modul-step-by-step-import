<?php

use Bitrix\Main\Application;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Loader;
use Bitrix\Main\ModuleManager;
use Custom\Cars\Install\Installer;

class custom_cars extends CModule
{
    public $MODULE_ID = 'custom.cars';
    public $MODULE_VERSION;
    public $MODULE_VERSION_DATE;
    public $MODULE_NAME = 'Car Import';
    public $MODULE_DESCRIPTION = 'Cars smart process with step-by-step Excel import.';
    public $PARTNER_NAME = 'Custom';
    public $PARTNER_URI = '';

    private const LINKS = [
        '/cars_import' => '/cars_import',
        '/js/custom/cars-form' => '/bitrix/js/custom/cars-form',
        '/js/custom/cars-import' => '/bitrix/js/custom/cars-import',
        '/components/custom.cars' => '/bitrix/components/custom.cars',
    ];

    private const INSTALLED_PATHS = [
        '/cars_import',
        '/bitrix/js/custom/cars-form',
        '/bitrix/js/custom/cars-import',
        '/bitrix/js/custom.cars',
        '/bitrix/components/custom.cars',
        '/upload/tmp/custom.cars',
        '/bitrix/tmp/custom.cars',
    ];

    public function __construct()
    {
        include __DIR__ . '/version.php';

        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
    }

    public function DoInstall()
    {
        global $APPLICATION;

        $dataInstalled = false;

        try {
            ModuleManager::registerModule($this->MODULE_ID);

            if (!Loader::includeModule($this->MODULE_ID)) {
                throw new RuntimeException('The module ' . $this->MODULE_ID . ' cannot be loaded.');
            }

            (new Installer())->install();
            $dataInstalled = true;

            $this->installFiles();
        } catch (Throwable $e) {
            $this->rollbackInstall($dataInstalled);
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    public function DoUninstall()
    {
        global $APPLICATION;

        try {
            Loader::includeModule($this->MODULE_ID);

            (new Installer())->uninstall();
            $this->removeFiles();

            Option::delete($this->MODULE_ID);
            ModuleManager::unRegisterModule($this->MODULE_ID);
        } catch (Throwable $e) {
            $APPLICATION->ThrowException($e->getMessage());

            return false;
        }

        return true;
    }

    public function installFiles(): void
    {
        $root = Application::getDocumentRoot();

        $this->removePath('/bitrix/js/custom.cars');

        foreach (self::LINKS as $from => $to) {
            $source = __DIR__ . $from;
            $target = $root . $to;

            $this->removePath($to);

            if (!is_dir(dirname($target)) && !mkdir(dirname($target), 0755, true)) {
                throw new RuntimeException('Cannot create the directory ' . dirname($target) . '.');
            }

            if (!@symlink($source, $target) && !CopyDirFiles($source, $target, true, true)) {
                throw new RuntimeException('Cannot link or copy ' . $source . ' to ' . $target . '.');
            }
        }
    }

    private function removeFiles(): void
    {
        foreach (self::INSTALLED_PATHS as $path) {
            $this->removePath($path);
        }
    }

    private function removePath(string $relativePath): void
    {
        $full = Application::getDocumentRoot() . $relativePath;

        if (is_link($full)) {
            @unlink($full) || @rmdir($full);
        } elseif (file_exists($full)) {
            DeleteDirFilesEx($relativePath);
        }
    }

    private function rollbackInstall(bool $dataInstalled): void
    {
        $steps = [
            fn () => $dataInstalled && (new Installer())->rollback(),
            fn () => $this->removeFiles(),
            fn () => Option::delete($this->MODULE_ID),
        ];

        foreach ($steps as $step) {
            try {
                $step();
            } catch (Throwable $e) {
                AddMessage2Log($e->getMessage(), $this->MODULE_ID);
            }
        }

        if (ModuleManager::isModuleInstalled($this->MODULE_ID)) {
            ModuleManager::unRegisterModule($this->MODULE_ID);
        }
    }
}
