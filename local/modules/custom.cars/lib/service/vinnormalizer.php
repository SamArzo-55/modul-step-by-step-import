<?php

namespace Custom\Cars\Service;

final class VinNormalizer
{
    public static function normalize(string $vin): string
    {
        return mb_strtoupper(preg_replace('/\s+/u', '', $vin) ?? $vin);
    }
}
