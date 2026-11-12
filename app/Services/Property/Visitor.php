<?php

namespace App\Services\Property;

use Illuminate\Support\Str;

// Who is browsing: the signed-in user, or a guest id kept in the session (so a guest's recent searches and views survive page loads).
class Visitor
{
    public static function id(): string
    {
        if ($id = auth()->id()) return 'u:' . $id;
        $session = request()->hasSession() ? request()->session() : null;
        if (!$session) return 'ip:' . sha1(request()->ip() . request()->userAgent());
        if (!$session->has('property_visitor')) $session->put('property_visitor', 'g:' . Str::uuid());
        return $session->get('property_visitor');
    }

    /** Ids to look up activity for: a user also sees what they did as a guest earlier in this session. */
    public static function ids(): array
    {
        $guest = request()->hasSession() ? request()->session()->get('property_visitor') : null;
        return array_values(array_filter(array_unique([self::id(), $guest])));
    }
}
