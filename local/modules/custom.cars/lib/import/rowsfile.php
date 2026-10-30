<?php

namespace Custom\Cars\Import;

use RuntimeException;

final class RowsFile
{
    public function append(string $path, array $rows, int $validSize): int
    {
        $handle = @fopen($path, 'c+b');

        if (!$handle) {
            throw new RuntimeException('Cannot open the import file ' . $path);
        }

        try {
            ftruncate($handle, $validSize);
            fseek($handle, $validSize);

            foreach ($rows as $row) {
                $line = json_encode($row, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR) . "\n";
                fwrite($handle, $line);
                $validSize += strlen($line);
            }
        } finally {
            fclose($handle);
        }

        return $validSize;
    }

    public function read(string $path, int $position, int $limit): array
    {
        $handle = @fopen($path, 'rb');

        if (!$handle) {
            throw new RuntimeException('Cannot open import file ' . $path . '. Upload the file again.');
        }

        try {
            fseek($handle, $position);

            $entries = [];
            $exhausted = false;

            while (count($entries) < $limit) {
                $line = fgets($handle);

                if ($line === false) {
                    $exhausted = true;
                    break;
                }

                $row = json_decode($line, true);
                $entries[] = ['row' => is_array($row) ? $row : null, 'end' => (int) ftell($handle)];
            }

            return ['entries' => $entries, 'exhausted' => $exhausted];
        } finally {
            fclose($handle);
        }
    }
}
