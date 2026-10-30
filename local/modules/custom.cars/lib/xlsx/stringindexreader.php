<?php

namespace Custom\Cars\Xlsx;

use RuntimeException;

final class StringIndexReader
{
    private const CACHE_LIMIT = 20000;

    private $data;

    private $offsets;

    private int $dataSize;
    private int $count;

    private array $cache = [];

    public function __construct(string $dataPath, string $offsetsPath)
    {
        $data = @fopen($dataPath, 'rb');
        $offsets = @fopen($offsetsPath, 'rb');

        if (!$data || !$offsets) {
            throw new RuntimeException('The shared strings index is missing. Upload the file again.');
        }

        $this->data = $data;
        $this->offsets = $offsets;
        $this->dataSize = (int) fstat($data)['size'];
        $this->count = intdiv((int) fstat($offsets)['size'], 4);
    }

    public function __destruct()
    {
        fclose($this->data);
        fclose($this->offsets);
    }

    public function get(int $index): string
    {
        if ($index < 0 || $index >= $this->count) {
            return '';
        }

        if (isset($this->cache[$index])) {
            return $this->cache[$index];
        }

        fseek($this->offsets, $index * 4);
        $raw = (string) fread($this->offsets, 8);

        $start = unpack('V', substr($raw, 0, 4))[1];
        $end = strlen($raw) === 8 ? unpack('V', substr($raw, 4, 4))[1] : $this->dataSize;

        fseek($this->data, $start);
        $text = $end > $start ? (string) fread($this->data, $end - $start) : '';

        if (count($this->cache) >= self::CACHE_LIMIT) {
            $this->cache = [];
        }

        return $this->cache[$index] = $text;
    }
}
