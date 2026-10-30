<?php

namespace Custom\Cars\Xlsx;

use Custom\Cars\Config\ImportSettings;

final class RowReader
{
    private ElementScanner $scanner;
    private ?StringIndexReader $strings;

    public function __construct(string $sheetPath, ?StringIndexReader $strings)
    {
        $this->scanner = new ElementScanner($sheetPath, 'row');
        $this->strings = $strings;
    }

    public function read(int $position, int $maxRows, int $emptyRun, int $lastRow, float $deadline): array
    {
        $rows = [];

        $scan = $this->scanner->scan(
            $position,
            $maxRows,
            $deadline,
            function (string $xml) use (&$rows, &$emptyRun, &$lastRow): void {
                if ($emptyRun >= ImportSettings::MAX_EMPTY_ROWS) {
                    return;
                }

                [$number, $cells] = $this->parseRow($xml, $lastRow);
                $lastRow = $number;

                if ($cells) {
                    $emptyRun = 0;
                    $rows[$number] = $cells;
                } else {
                    $emptyRun++;
                }
            }
        );

        return [
            'rows' => $rows,
            'position' => $scan['position'],
            'eof' => $scan['eof'] || $emptyRun >= ImportSettings::MAX_EMPTY_ROWS,
            'emptyRun' => $emptyRun,
            'lastRow' => $lastRow,
        ];
    }

    private function parseRow(string $xml, int $lastRow): array
    {
        $end = strpos($xml, '>');
        $head = $end === false ? '' : substr($xml, 0, $end);
        $number = preg_match('/\sr="(\d+)"/', $head, $match) ? (int) $match[1] : $lastRow + 1;
        $cells = [];

        if (!preg_match_all('#<c\b([^>]*?)(?:/>|>(.*?)</c>)#s', $xml, $matches, PREG_SET_ORDER)) {
            return [$number, $cells];
        }

        foreach ($matches as $position => $cell) {
            $value = trim($this->cellValue($cell[1], $cell[2] ?? ''));

            if ($value !== '') {
                $cells[$this->columnIndex($cell[1], $position)] = $value;
            }
        }

        return [$number, $cells];
    }

    private function cellValue(string $attributes, string $body): string
    {
        $type = preg_match('/\st="(\w+)"/', $attributes, $match) ? $match[1] : '';

        return match ($type) {
            's' => $this->strings?->get((int) $this->tagText($body, 'v')) ?? '',
            'inlineStr' => preg_match('#<is\b.*?</is>#s', $body, $inline) ? XmlText::textOf($inline[0]) : '',
            'e' => '',
            default => XmlText::decode($this->tagText($body, 'v')),
        };
    }

    private function tagText(string $xml, string $tag): string
    {
        return preg_match('#<' . $tag . '\b[^>]*>(.*?)</' . $tag . '>#s', $xml, $match) ? $match[1] : '';
    }

    private function columnIndex(string $attributes, int $fallback): int
    {
        if (!preg_match('/\sr="([A-Z]+)\d*"/', $attributes, $match)) {
            return $fallback;
        }

        $index = 0;

        foreach (str_split($match[1]) as $letter) {
            $index = $index * 26 + ord($letter) - 64;
        }

        return $index - 1;
    }
}
