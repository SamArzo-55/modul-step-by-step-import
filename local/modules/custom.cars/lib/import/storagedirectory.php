<?php

namespace Custom\Cars\Import;

use Bitrix\Main\Application;
use Custom\Cars\Config\ImportSettings;
use RuntimeException;

final class StorageDirectory
{
    public function candidates(): array
    {
        $root = rtrim(Application::getDocumentRoot(), '/');

        return [
            $root . '/upload/tmp/custom.cars',
            $root . '/bitrix/tmp/custom.cars',
            rtrim(sys_get_temp_dir(), '/') . '/custom.cars',
        ];
    }

    public function resolve(): string
    {
        foreach ($this->candidates() as $directory) {
            if ((is_dir($directory) || @mkdir($directory, 0775, true)) && is_writable($directory)) {
                if (!is_file($directory . '/.htaccess')) {
                    @file_put_contents($directory . '/.htaccess', "Order allow,deny\nDeny from all\n");
                }

                return $directory;
            }
        }

        throw new RuntimeException('No writable directory for import files. Checked: ' . implode(', ', $this->candidates()));
    }

    public function path(string $token, string $extension): string
    {
        foreach ($this->candidates() as $directory) {
            if (is_file($directory . '/' . $token . '.' . $extension)) {
                return $directory . '/' . $token . '.' . $extension;
            }
        }

        return $this->resolve() . '/' . $token . '.' . $extension;
    }

    public function cleanupExpired(): void
    {
        foreach (glob($this->resolve() . '/*') ?: [] as $file) {
            if (is_file($file) && filemtime($file) < time() - ImportSettings::SESSION_TTL) {
                @unlink($file);
            }
        }
    }

    public function deleteSession(string $token): void
    {
        foreach ($this->candidates() as $directory) {
            foreach (glob($directory . '/' . $token . '.*') ?: [] as $file) {
                @unlink($file);
            }
        }
    }
}
