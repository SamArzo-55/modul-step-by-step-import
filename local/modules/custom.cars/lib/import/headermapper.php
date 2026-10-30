<?php

namespace Custom\Cars\Import;

use Custom\Cars\Config\Car;

final class HeaderMapper
{
    private const EXTRA_HEADERS = [
        'TITLE' => ['название', 'title', 'name'],
        'CONTACT' => ['контакт', 'contact'],
        'PHONE' => ['контактный номер владельца', 'номер владельца', 'телефон владельца', 'телефон', 'phone'],
        'RESPONSIBLE' => ['ответственный', 'responsible'],
    ];

    private ?array $map = null;

    public static function normalize(string $title): string
    {
        $title = mb_strtolower(str_replace("\xC2\xA0", ' ', $title));
        $title = str_replace('ё', 'е', $title);
        $title = preg_replace('/\s+/u', ' ', $title) ?? $title;

        return trim($title, " \t\n\r:.;");
    }

    public function columns(array $header): array
    {
        $map = $this->headerMap();
        $columns = [];

        foreach ($header as $index => $title) {
            $code = $map[self::normalize((string) $title)] ?? null;

            if ($code !== null && !in_array($code, $columns, true)) {
                $columns[$index] = $code;
            }
        }

        return $columns;
    }

    private function headerMap(): array
    {
        if ($this->map !== null) {
            return $this->map;
        }

        $map = [];

        foreach (Car::codes() as $code) {
            foreach (Car::headers($code) as $title) {
                $map[self::normalize($title)] = $code;
            }
        }

        foreach (self::EXTRA_HEADERS as $code => $titles) {
            foreach ($titles as $title) {
                $map[self::normalize($title)] = $code;
            }
        }

        return $this->map = $map;
    }
}
