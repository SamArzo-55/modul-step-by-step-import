<?php

namespace Custom\Cars\Xlsx;

final class XmlText
{
    public static function decode(string $text): string
    {
        return html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public static function textOf(string $xml): string
    {
        $xml = preg_replace('#<rPh\b.*?</rPh>#s', '', $xml) ?? $xml;

        if (!preg_match_all('#<t\b[^>]*?(?:/>|>(.*?)</t>)#s', $xml, $matches)) {
            return '';
        }

        return self::decode(implode('', $matches[1]));
    }

    public static function attributes(string $tag): array
    {
        preg_match_all('/([\w:.-]+)\s*=\s*"([^"]*)"/', $tag, $pairs, PREG_SET_ORDER);

        $attributes = [];

        foreach ($pairs as $pair) {
            $attributes[$pair[1]] = self::decode($pair[2]);
        }

        return $attributes;
    }
}
