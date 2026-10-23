<?php

namespace App\Http\Controllers;

use App\Services\Export\Xlsx;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

// Owner-only visitor log: signed-in members by name, guests by approximate state and device, per page and per company.
// Guests cannot be identified by name; only members who signed in can.
class AdminVisitorsController extends Controller
{
    public function index(Request $r)
    {
        [$q, $f] = $this->query($r);
        $members = (clone $q)->whereNotNull('a.user_id')->groupBy('a.user_id', 'u.name', 'u.email', 'u.role')
            ->selectRaw('a.user_id, u.name, u.email, u.role, COUNT(*) views, COUNT(DISTINCT a.path) pages, MIN(a.created_at) first_seen, MAX(a.created_at) last_seen')
            ->orderByDesc('last_seen')->limit(200)->get();
        $topPagesByUser = (clone $q)->whereIn('a.user_id', $members->pluck('user_id'))->groupBy('a.user_id', 'a.path')->selectRaw('a.user_id, a.path, COUNT(*) n')->get()
            ->groupBy('user_id')->map(fn ($g) => $g->sortByDesc('n')->take(3)->pluck('path'));
        $companies = (clone $q)->where('a.path', 'like', '/company-insights/%')->where('a.path', 'not like', '/company-insights/explore%')->where('a.path', 'not like', '%/report.%')
            ->groupBy('a.path')->selectRaw("a.path, COUNT(*) views, COUNT(DISTINCT a.user_id) members, COUNT(DISTINCT CASE WHEN a.user_id IS NULL THEN COALESCE(a.visitor_key, a.session_id) END) guests, GROUP_CONCAT(DISTINCT u.name ORDER BY u.name SEPARATOR ', ') member_names")
            ->orderByDesc('views')->limit(50)->get();
        $recent = (clone $q)->orderByDesc('a.id')->limit(200)->get(['a.id', 'a.created_at', 'a.path', 'a.user_id', 'u.name', 'u.email', 'a.user_agent', 'dv.state', 'dv.country_code']);
        $totals = (clone $q)->selectRaw('COUNT(*) views, COUNT(DISTINCT a.user_id) members, COUNT(DISTINCT CASE WHEN a.user_id IS NULL THEN COALESCE(a.visitor_key, a.session_id) END) guests')->first();
        $states = (clone $q)->whereNull('a.user_id')->whereNotNull('dv.state')->groupBy('dv.state')->selectRaw('dv.state, COUNT(*) views')->orderByDesc('views')->limit(12)->pluck('views', 'state');
        return view('admin.visitors', compact('members', 'topPagesByUser', 'companies', 'recent', 'totals', 'states', 'f') + ['device' => fn ($ua) => $this->device($ua)]);
    }

    public function export(Request $r, Xlsx $xlsx)
    {
        [$q, $f] = $this->query($r);
        $rows = (clone $q)->orderByDesc('a.id')->limit(50000)->get(['a.created_at', 'u.name', 'u.email', 'a.path', 'dv.state', 'dv.country_code', 'a.user_agent'])
            ->map(fn ($v) => [Carbon::parse($v->created_at)->format('Y-m-d H:i:s'), $v->name ?? 'Guest', $v->email ?? '', $v->path, $v->state ?? '', $v->country_code ?? '', $this->device($v->user_agent)])->all();
        $path = $xlsx->write(['Visits' => [['Time', 'Visitor', 'Email', 'Page', 'State', 'Country', 'Device'], $rows]]);
        return response()->download($path, 'visitors-' . $f['from'] . '-to-' . $f['to'] . '.xlsx')->deleteFileAfterSend();
    }

    private function query(Request $r): array
    {
        $f = ['from' => $r->query('from', now()->subDays(6)->toDateString()), 'to' => $r->query('to', now()->toDateString()), 'who' => $r->query('who', 'all'), 'q' => trim((string) $r->query('q', '')), 'page' => trim((string) $r->query('page', ''))];
        $q = DB::table('activity_logs as a')->leftJoin('users as u', 'u.id', '=', 'a.user_id')
            ->leftJoin('daily_visitors as dv', fn ($j) => $j->on('dv.visitor_key', '=', 'a.visitor_key')->whereRaw('dv.visit_date = DATE(a.created_at)'))
            ->where('a.action', 'page_view')->whereBetween('a.created_at', [Carbon::parse($f['from'])->startOfDay(), Carbon::parse($f['to'])->endOfDay()]);
        if ($f['who'] === 'members') $q->whereNotNull('a.user_id');
        if ($f['who'] === 'guests') $q->whereNull('a.user_id');
        if ($f['q'] !== '') $q->where(fn ($w) => $w->where('u.name', 'like', '%' . $f['q'] . '%')->orWhere('u.email', 'like', '%' . $f['q'] . '%'));
        if ($f['page'] !== '') $q->where('a.path', 'like', '%' . $f['page'] . '%');
        return [$q, $f];
    }

    private function device(?string $ua): string
    {
        $ua = (string) $ua;
        $kind = preg_match('/iPad|Tablet/i', $ua) ? 'Tablet' : (preg_match('/Mobile|Android|iPhone/i', $ua) ? 'Mobile' : 'Desktop');
        $browser = match (true) { str_contains($ua, 'Edg/') => 'Edge', str_contains($ua, 'OPR/') => 'Opera', str_contains($ua, 'Chrome/') => 'Chrome', str_contains($ua, 'Firefox/') => 'Firefox', str_contains($ua, 'Safari/') => 'Safari', default => 'Other' };
        $os = match (true) { str_contains($ua, 'Windows') => 'Windows', str_contains($ua, 'Android') => 'Android', (bool) preg_match('/iPhone|iPad|iOS/', $ua) => 'iOS', str_contains($ua, 'Mac OS') => 'macOS', str_contains($ua, 'Linux') => 'Linux', default => '' };
        return trim("$kind · $browser" . ($os ? " · $os" : ''));
    }
}
