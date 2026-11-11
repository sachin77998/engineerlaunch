<?php

namespace App\Support;

// Public display name on the forum: first name plus last initial ("Arpita K"), never the full name or email.
class ForumName
{
    public static function short(?string $name): string
    {
        $parts = preg_split('/\s+/u', trim((string) $name)) ?: [];
        if (!$parts || $parts[0] === '') return 'Member';
        return count($parts) > 1 ? $parts[0] . ' ' . mb_strtoupper(mb_substr(end($parts), 0, 1)) : $parts[0];
    }
}
