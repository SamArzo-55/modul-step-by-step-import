<?php

namespace Custom\Cars\Import;

use Bitrix\Main\UserTable;

final class UserResolver
{
    private array $cache = [];

    public function resolve(string $fullName): ?int
    {
        if ($fullName === '') {
            return null;
        }

        if (!array_key_exists($fullName, $this->cache)) {
            $this->cache[$fullName] = $this->find(...FullName::split($fullName));
        }

        return $this->cache[$fullName];
    }

    private function find(string $name, string $lastName): ?int
    {
        $user = UserTable::getList([
            'select' => ['ID'],
            'filter' => ['=NAME' => $name, '=LAST_NAME' => $lastName, '=ACTIVE' => 'Y'],
            'limit' => 1,
        ])->fetch();

        return $user ? (int) $user['ID'] : null;
    }
}
