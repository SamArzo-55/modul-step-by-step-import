<?php

namespace Custom\Cars\Import;

use Bitrix\Crm\ContactTable;
use CCrmContact;

final class ContactResolver
{
    private array $cache = [];

    private array $pending = [];

    public function resolve(string $fullName, string $phone): ?int
    {
        if ($fullName === '') {
            return null;
        }

        if (array_key_exists($fullName, $this->cache)) {
            return $this->cache[$fullName];
        }

        [$name, $lastName] = FullName::split($fullName);
        $id = $this->find($name, $lastName);

        if ($id === null) {
            $id = $this->create($name, $lastName, $phone);
            $this->pending[] = $fullName;
        }

        return $this->cache[$fullName] = $id;
    }

    public function confirm(): void
    {
        $this->pending = [];
    }

    public function revert(): void
    {
        foreach ($this->pending as $fullName) {
            unset($this->cache[$fullName]);
        }

        $this->pending = [];
    }

    private function find(string $name, string $lastName): ?int
    {
        $contact = ContactTable::getList([
            'select' => ['ID'],
            'filter' => ['=NAME' => $name, '=LAST_NAME' => $lastName],
            'order' => ['ID' => 'ASC'],
            'limit' => 1,
        ])->fetch();

        return $contact ? (int) $contact['ID'] : null;
    }

    private function create(string $name, string $lastName, string $phone): ?int
    {
        $fields = ['NAME' => $name, 'LAST_NAME' => $lastName, 'OPENED' => 'Y'];
        $digits = preg_replace('/\D/', '', $phone);

        if ($digits !== '') {
            $fields['FM'] = ['PHONE' => ['n1' => ['VALUE' => '+' . $digits, 'VALUE_TYPE' => 'WORK']]];
        }

        $id = (new CCrmContact(false))->Add($fields);

        return $id ? (int) $id : null;
    }
}
