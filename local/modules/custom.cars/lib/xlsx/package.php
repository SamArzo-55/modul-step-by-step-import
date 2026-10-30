<?php

namespace Custom\Cars\Xlsx;

use RuntimeException;
use ZipArchive;

final class Package
{
    private const WORKBOOK = 'xl/workbook.xml';
    private const RELATIONSHIPS = 'xl/_rels/workbook.xml.rels';
    private const SHARED_STRINGS = 'xl/sharedStrings.xml';
    private const DEFAULT_SHEET = 'xl/worksheets/sheet1.xml';

    private ZipArchive $zip;

    public function __construct(string $path)
    {
        if ($path === '' || !is_file($path)) {
            throw new RuntimeException('The uploaded file was not found on the server (check upload_max_filesize / post_max_size).');
        }

        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            throw new RuntimeException('The file is not a valid .xlsx workbook.');
        }

        $this->zip = $zip;
    }

    public function sheetPath(): string
    {
        $workbook = (string) $this->zip->getFromName(self::WORKBOOK);
        $relationships = (string) $this->zip->getFromName(self::RELATIONSHIPS);

        if (!preg_match('#<sheet\b[^>]*>#', $workbook, $sheet)) {
            return self::DEFAULT_SHEET;
        }

        $id = $this->relationshipId(XmlText::attributes($sheet[0]));

        preg_match_all('#<Relationship\b[^>]*>#', $relationships, $tags);

        foreach ($tags[0] as $tag) {
            $attributes = XmlText::attributes($tag);

            if ($id !== '' && ($attributes['Id'] ?? '') === $id && isset($attributes['Target'])) {
                $target = ltrim($attributes['Target'], '/');

                return str_starts_with($target, 'xl/') ? $target : 'xl/' . $target;
            }
        }

        return self::DEFAULT_SHEET;
    }

    public function sharedStringsPath(): ?string
    {
        return $this->zip->locateName(self::SHARED_STRINGS) !== false ? self::SHARED_STRINGS : null;
    }

    public function extract(string $entry, string $target): int
    {
        $source = $this->zip->getStream($entry);
        $output = @fopen($target, 'wb');

        if (!$source || !$output) {
            throw new RuntimeException('Cannot extract "' . $entry . '" from the workbook.');
        }

        try {
            $bytes = stream_copy_to_stream($source, $output);
        } finally {
            fclose($source);
            fclose($output);
        }

        if ($bytes === false) {
            throw new RuntimeException('Cannot extract "' . $entry . '" from the workbook.');
        }

        return $bytes;
    }

    public function close(): void
    {
        $this->zip->close();
    }

    private function relationshipId(array $attributes): string
    {
        foreach ($attributes as $name => $value) {
            if ($name === 'r:id' || preg_match('/^\w+:id$/', $name)) {
                return $value;
            }
        }

        return '';
    }
}
