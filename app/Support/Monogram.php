<?php

namespace App\Support;

// Initials in a coloured circle for directory entries (we do not reuse other organisations' logos).
final class Monogram
{
    /** @return array{0:string,1:string} initials, background colour */
    public static function for(string $name): array
    {
        $words = preg_split('/[\s\/&,.-]+/', trim(preg_replace('/\(.*?\)|—.*$/u', '', $name)), -1, PREG_SPLIT_NO_EMPTY);
        $words = array_values(array_filter($words, fn ($w) => !in_array(mb_strtolower($w), ['of', 'the', 'and', 'municipal', 'corporation', 'council'], true))) ?: ['?'];
        $initials = mb_strtoupper(mb_substr($words[0], 0, 1) . mb_substr($words[1] ?? '', 0, 1));
        return [$initials, 'hsl(' . (crc32($name) % 360) . ' 55% 40%)'];
    }
}
