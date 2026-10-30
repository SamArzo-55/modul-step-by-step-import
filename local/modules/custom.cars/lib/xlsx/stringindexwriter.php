<?php

namespace Custom\Cars\Xlsx;

use RuntimeException;

final class StringIndexWriter
{
    private $data;

    private $offsets;

    private int $dataSize;
    private int $offsetsSize;

    public function __construct(string $dataPath, string $offsetsPath, int $dataSize, int $offsetsSize)
    {
        $this->data = $this->open($dataPath, $dataSize);
        $this->offsets = $this->open($offsetsPath, $offsetsSize);
        $this->dataSize = $dataSize;
        $this->offsetsSize = $offsetsSize;
    }

    public function append(string $text): void
    {
        fwrite($this->offsets, pack('V', $this->dataSize));
        fwrite($this->data, $text);

        $this->offsetsSize += 4;
        $this->dataSize += strlen($text);
    }

    public function sizes(): array
    {
        return ['data' => $this->dataSize, 'offsets' => $this->offsetsSize];
    }

    public function close(): void
    {
        fclose($this->data);
        fclose($this->offsets);
    }

    private function open(string $path, int $size)
    {
        $handle = @fopen($path, 'c+b');

        if (!$handle) {
            throw new RuntimeException('Cannot open the working file ' . $path . '.');
        }

        ftruncate($handle, $size);
        fseek($handle, $size);

        return $handle;
    }
}
