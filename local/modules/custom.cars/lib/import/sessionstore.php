<?php

namespace Custom\Cars\Import;

use Bitrix\Main\Engine\CurrentUser;
use RuntimeException;

final class SessionStore
{
    private StorageDirectory $directory;
    public function __construct(?StorageDirectory $directory = null)
    {
        $this->directory = $directory ?? new StorageDirectory();
    }

    public function create(): string
    {
        $this->directory->cleanupExpired();

        $token = ImportToken::generate();

        $this->save($token, [
            'user' => (int) CurrentUser::get()->getId(),
            'created' => time(),
            'sheetSize' => 0,
            'stringsSize' => 0,
            'stringsPos' => 0,
            'strBytes' => 0,
            'idxBytes' => 0,
            'stringsDone' => false,
            'sheetPos' => 0,
            'emptyRun' => 0,
            'lastRow' => 0,
            'rowsBytes' => 0,
            'headerDone' => false,
            'columns' => [],
            'parseDone' => false,
            'total' => 0,
            'offset' => 0,
            'pos' => 0,
            'imported' => 0,
            'skipped' => 0,
            'sampleError' => null,
        ]);

        return $token;
    }

    public function state(string $token): ?array
    {
        if (!ImportToken::isValid($token)) {
            return null;
        }

        $path = $this->path($token, 'state');
        $state = is_file($path) ? json_decode((string) file_get_contents($path), true) : null;

        if (!is_array($state) || (int) ($state['user'] ?? 0) !== (int) CurrentUser::get()->getId()) {
            return null;
        }

        return $state;
    }

    public function save(string $token, array $state): void
    {
        $path = $this->path($token, 'state');
        $temp = $path . '.tmp';

        if (file_put_contents($temp, json_encode($state, JSON_UNESCAPED_UNICODE)) === false || !@rename($temp, $path)) {
            throw new RuntimeException('Cannot write import state ' . $path);
        }
    }

    public function path(string $token, string $extension): string
    {
        return $this->directory->path($token, $extension);
    }

    public function delete(string $token): void
    {
        if (ImportToken::isValid($token)) {
            $this->directory->deleteSession($token);
        }
    }
}
