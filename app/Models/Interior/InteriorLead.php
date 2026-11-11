<?php

namespace App\Models\Interior;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InteriorLead extends Model
{
    protected $guarded = [];

    protected $casts = ['estimate' => 'array'];

    public function user() { return $this->belongsTo(User::class); }
    public function idea() { return $this->belongsTo(InteriorIdea::class, 'interior_idea_id'); }

    public function statusLabel(): string
    {
        return config('interiors.statuses')[$this->status] ?? ucfirst($this->status);
    }

    /** Room-wise estimate from room counts and a package (mirrored in the estimator's JavaScript). */
    public static function estimate(array $rooms, string $package): array
    {
        $c = config('interiors');
        $mult = $c['packages'][$package][1] ?? 1;
        $lines = [];
        foreach ($rooms as $room => $n) {
            $n = max(0, min((int) $n, $c['max_rooms']));
            if (!$n || !isset($c['room_rates'][$room])) continue;
            $lines[$room] = ['label' => $c['rooms'][$room][0], 'count' => $n, 'amount' => (int) round($c['room_rates'][$room] * $mult * $n, -3)];
        }
        $total = array_sum(array_column($lines, 'amount'));
        $spread = $total * $c['range_pct'] / 100;
        return ['package' => $package, 'lines' => $lines, 'total' => $total, 'low' => (int) round($total - $spread, -3), 'high' => (int) round($total + $spread, -3)];
    }
}
