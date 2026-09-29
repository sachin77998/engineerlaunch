<?php
namespace App\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class VisitorAnalytics
{
    public function record(string $visitorId): array
    {
        $date = now(config('visitor_analytics.timezone'))->toDateString();
        $key = hash_hmac('sha256', $visitorId, config('app.key'));
        $match = ['visit_date'=>$date, 'visitor_key'=>$key];
        DB::table('daily_visitors')->insertOrIgnore($match + [
            'page_views'=>0, 'first_seen_at'=>now(), 'last_seen_at'=>now(),
        ]);
        DB::table('daily_visitors')->where($match)->increment('page_views', 1, ['last_seen_at'=>now()]);
        return $match;
    }

    public function locate(array $match, string $ip): void
    {
        if (DB::table('daily_visitors')->where($match)->whereNotNull('state')->where('state', '<>', '')->exists()) return;
        $location = app(VisitorLocation::class)->lookup($ip);
        if ($location) DB::table('daily_visitors')->where($match)->update($location);
    }

    public function summary(?string $date = null): array
    {
        $timezone = config('visitor_analytics.timezone');
        $day = CarbonImmutable::parse($date ?? now($timezone)->toDateString(), $timezone)->startOfDay();
        $base = DB::table('daily_visitors')->where('visit_date', $day->toDateString());
        $states = (clone $base)->select('country_code', 'state')->selectRaw('COUNT(*) as visitors, SUM(page_views) as page_views')
            ->groupBy('country_code', 'state')->orderByDesc('visitors')->get();
        $history = DB::table('daily_visitors')->whereBetween('visit_date', [$day->subDays(29)->toDateString(), $day->toDateString()])
            ->select('visit_date')->selectRaw('COUNT(*) as visitors, SUM(page_views) as page_views')
            ->groupBy('visit_date')->get()->keyBy('visit_date');
        $days = [];
        for ($i=0; $i<30; $i++) {
            $d = $day->subDays($i)->toDateString();
            $days[] = ['date'=>$d, 'visitors'=>(int)($history[$d]->visitors ?? 0), 'page_views'=>(int)($history[$d]->page_views ?? 0)];
        }
        return ['date'=>$day->toDateString(), 'timezone'=>$timezone, 'visitors'=>(clone $base)->count(),
            'page_views'=>(int)(clone $base)->sum('page_views'), 'states'=>$states, 'days'=>$days];
    }
}
