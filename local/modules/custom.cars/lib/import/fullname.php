<?php

namespace Custom\Cars\Import;

final class FullName
{
    public static function split(string $fullName): array
    {
        return array_pad(explode(' ', $fullName, 2), 2, '');
    }
}
