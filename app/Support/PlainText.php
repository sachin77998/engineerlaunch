<?php

namespace App\Support;

// Imported job and article text as plain text: tags removed, entities (&nbsp;, &amp;) decoded, whitespace collapsed.
final class PlainText
{
    public static function from(?string $html, bool $keepLines = false): string
    {
        $text = (string) $html;
        if ($keepLines) $text = preg_replace('~<\s*(br|/p|/li|/h[1-6]|/div)\b[^>]*>~i', "\n", $text);
        $text = html_entity_decode(strip_tags($text), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = str_replace("\u{a0}", ' ', $text);
        $text = $keepLines ? preg_replace(["~[ \t]+~u", "~\n\s*\n+~u"], [' ', "\n\n"], $text) : preg_replace('~\s+~u', ' ', $text);
        return trim($text);
    }
}
