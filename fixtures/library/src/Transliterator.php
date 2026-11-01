<?php
declare(strict_types=1);
namespace Acme\Slugger;
final class Transliterator
{
    private const array MAP = ['à' => 'a', 'é' => 'e', 'è' => 'e',
        'ì' => 'i', 'ò' => 'o', 'ù' => 'u', 'ñ' => 'n', 'ç' => 'c'];
    public function ascii(string $text): string
    {
        return strtr(mb_strtolower($text), self::MAP);
    }
}
