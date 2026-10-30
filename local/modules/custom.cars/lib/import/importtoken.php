<?php

namespace Custom\Cars\Import;

final class ImportToken
{
    public static function generate(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function isValid(string $token): bool
    {
        return (bool) preg_match('/^[a-f0-9]{32}$/', $token);
    }
}
