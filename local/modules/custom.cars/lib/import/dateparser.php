<?php

namespace Custom\Cars\Import;

use Bitrix\Main\Type\DateTime;

final class DateParser
{
    private const FORMATS = [
        'd.m.Y H:i:s', 'd.m.Y H:i', 'd.m.Y',
        'Y-m-d H:i:s', 'Y-m-d H:i', 'Y-m-d',
        'd/m/Y H:i:s', 'd/m/Y H:i', 'd/m/Y',
    ];

    public function parse(string $value): ?DateTime
    {
        if (is_numeric($value)) {
            $seconds = (int) round(((float) $value - 25569) * 86400);

            return new DateTime(gmdate('Y-m-d H:i:s', $seconds), 'Y-m-d H:i:s');
        }

        foreach (self::FORMATS as $format) {
            $php = \DateTime::createFromFormat('!' . $format, $value);
            $errors = \DateTime::getLastErrors();

            if ($php !== false && (!$errors || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return DateTime::createFromPhp($php);
            }
        }

        return null;
    }
}
