<?php

namespace Custom\Cars\Xlsx;

use Custom\Cars\Config\ImportSettings;
use RuntimeException;

final class ElementScanner
{
    private string $path;
    private string $tag;
    private int $chunkSize;

    public function __construct(string $path, string $tag, int $chunkSize = ImportSettings::XML_CHUNK)
    {
        $this->path = $path;
        $this->tag = $tag;
        $this->chunkSize = $chunkSize;
    }

    public function scan(int $position, int $maxElements, float $deadline, callable $consumer): array
    {
        $handle = @fopen($this->path, 'rb');

        if (!$handle) {
            throw new RuntimeException('Cannot open the working file ' . $this->path . '. Upload the file again.');
        }

        try {
            fseek($handle, $position);

            $buffer = '';
            $cursor = 0;
            $count = 0;
            $eof = false;

            while ($count < $maxElements && ($count === 0 || microtime(true) < $deadline)) {
                $range = $this->next($buffer, $cursor);

                if ($range === null) {
                    $cursor = $this->skipJunk($buffer, $cursor);

                    if ($eof) {
                        break;
                    }

                    $chunk = fread($handle, $this->chunkSize);

                    if ($chunk === false || $chunk === '') {
                        $eof = true;
                        continue;
                    }

                    $position += $cursor;
                    $buffer = substr($buffer, $cursor) . $chunk;
                    $cursor = 0;
                    continue;
                }

                $consumer(substr($buffer, $range[0], $range[1] - $range[0]));
                $cursor = $range[1];
                $count++;
            }

            return [
                'position' => $position + $cursor,
                'eof' => $eof && $this->next($buffer, $cursor) === null,
                'count' => $count,
            ];
        } finally {
            fclose($handle);
        }
    }

    private function next(string $buffer, int $cursor): ?array
    {
        $open = '<' . $this->tag;
        $length = strlen($open);

        while (true) {
            $start = strpos($buffer, $open, $cursor);

            if ($start === false || !isset($buffer[$start + $length])) {
                return null;
            }

            if (!in_array($buffer[$start + $length], [' ', '>', '/', "\n", "\r", "\t"], true)) {
                $cursor = $start + $length;
                continue;
            }

            $end = strpos($buffer, '>', $start);

            if ($end === false) {
                return null;
            }

            if ($buffer[$end - 1] === '/') {
                return [$start, $end + 1];
            }

            $close = strpos($buffer, '</' . $this->tag . '>', $end);

            return $close === false ? null : [$start, $close + $length + 2];
        }
    }

    private function skipJunk(string $buffer, int $cursor): int
    {
        $open = '<' . $this->tag;

        return strpos($buffer, $open, $cursor) === false ? max($cursor, strlen($buffer) - strlen($open)) : $cursor;
    }
}
