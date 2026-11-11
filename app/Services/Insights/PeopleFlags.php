<?php

namespace App\Services\Insights;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Founder and leadership movements from the officer register (MGT-7 filings and the data providers). The DIN
 * ties one person to every company they direct, so:
 *  - ventures:  a director of an existing company is a founding director of a company incorporated in the period
 *               (new and often not yet public: the "stealth startup" signal);
 *  - moves:     someone left one company and joined another between 30 days before and 180 days after leaving;
 *  - joined / left: appointments and cessations in the period.
 */
class PeopleFlags
{
    public const KINDS = ['ventures' => 'New ventures', 'moves' => 'Moves', 'joined' => 'Appointments', 'left' => 'Exits'];
    public const PERIODS = ['30' => 'Last 30 days', '90' => 'Last 90 days', '365' => 'Last 12 months'];

    public function flags(string $kind, int $days, ?string $q = null, int $limit = 200): Collection
    {
        $build = fn () => match ($kind) {
            'ventures' => $this->ventures($days, $q, $limit),
            'moves' => $this->moves($days, $q, $limit),
            default => $this->dated($kind === 'left' ? 'ceased_on' : 'appointed_on', $days, $q, $limit),
        };
        return filled($q) ? $build() : Cache::remember("people-flags:v1:$kind:$days", now()->addMinutes(30), $build);
    }

    /** @return array<string,int> flags per kind in the period, for the tab counts */
    public function counts(int $days): array
    {
        return Cache::remember("people-flags:counts:v1:$days", now()->addMinutes(30), fn () => collect(self::KINDS)->map(fn ($_, $kind) => $this->flags($kind, $days)->count())->all());
    }

    private function dated(string $column, int $days, ?string $q, int $limit): Collection
    {
        $rows = $this->officers()->whereBetween("o.$column", [now()->subDays($days)->toDateString(), now()->toDateString()])
            ->when($q, fn ($w, $v) => $this->search($w, $v))
            ->orderByDesc("o.$column")->limit($limit)
            ->get(['o.name', 'o.din', 'o.designation', "o.$column as on", 'c.name as company', 'c.slug', 'o.company_id']);
        return $this->withOtherBoards($rows);
    }

    private function moves(int $days, ?string $q, int $limit): Collection
    {
        return DB::table('company_officers as old')
            ->join('company_officers as o', fn ($j) => $j->on('o.din', '=', 'old.din')->on('o.company_id', '!=', 'old.company_id'))
            ->join('companies as c', 'c.id', '=', 'o.company_id')
            ->join('companies as oc', 'oc.id', '=', 'old.company_id')
            ->whereNotNull('old.din')->whereNotNull('old.ceased_on')
            ->whereBetween('o.appointed_on', [now()->subDays($days)->toDateString(), now()->toDateString()])
            ->whereRaw('o.appointed_on between date_sub(old.ceased_on, interval 30 day) and date_add(old.ceased_on, interval 180 day)')
            ->when($q, fn ($w, $v) => $this->search($w, $v, 'oc.name'))
            ->orderByDesc('o.appointed_on')->limit($limit)
            ->get(['o.name', 'o.din', 'o.designation', 'o.appointed_on as on', 'c.name as company', 'c.slug', 'oc.name as from_company', 'oc.slug as from_slug', 'old.designation as from_designation', 'old.ceased_on as left_on'])
            ->unique(fn ($r) => $r->din . '|' . $r->slug)->values();
    }

    private function ventures(int $days, ?string $q, int $limit): Collection
    {
        $rows = $this->officers()->join('company_registry_details as rd', 'rd.company_id', '=', 'o.company_id')
            ->whereNotNull('o.din')
            ->whereBetween('rd.incorporation_date', [now()->subDays($days)->toDateString(), now()->toDateString()])
            // Founding directors: appointed on incorporation or soon after (no date means appointed at incorporation).
            ->where(fn ($w) => $w->whereNull('o.appointed_on')->orWhereRaw('o.appointed_on <= date_add(rd.incorporation_date, interval 60 day)'))
            ->whereExists(fn ($e) => $e->selectRaw('1')->from('company_officers as prior')->whereColumn('prior.din', 'o.din')->whereColumn('prior.company_id', '!=', 'o.company_id')
                ->where(fn ($w) => $w->whereNull('prior.appointed_on')->orWhereColumn('prior.appointed_on', '<', 'rd.incorporation_date')))
            ->when($q, fn ($w, $v) => $this->search($w, $v))
            ->orderByDesc('rd.incorporation_date')->limit($limit)
            ->get(['o.name', 'o.din', 'o.designation', 'rd.incorporation_date as on', 'c.name as company', 'c.slug', 'c.industry', 'o.company_id']);
        if ($rows->isEmpty()) return $rows;
        // Where each founder already sits: their other boards, current ones first.
        $prior = DB::table('company_officers as p')->join('companies as pc', 'pc.id', '=', 'p.company_id')
            ->whereIn('p.din', $rows->pluck('din')->unique())->orderByRaw('p.ceased_on is not null')->orderByDesc('p.appointed_on')
            ->get(['p.din', 'p.company_id', 'pc.name', 'pc.slug', 'p.ceased_on'])->groupBy('din');
        return $rows->each(function ($r) use ($prior) {
            $r->boards = ($prior[$r->din] ?? collect())->where('company_id', '!=', $r->company_id)->unique('company_id')->take(4)->values();
        });
    }

    private function officers()
    {
        return DB::table('company_officers as o')->join('companies as c', 'c.id', '=', 'o.company_id');
    }

    private function search($query, string $q, ?string $alsoCompany = null)
    {
        return $query->where(fn ($w) => $w->where('o.name', 'like', '%' . $q . '%')->orWhere('o.din', $q)->orWhere('c.name', 'like', '%' . $q . '%')
            ->when($alsoCompany, fn ($x, $column) => $x->orWhere($column, 'like', '%' . $q . '%')));
    }

    // How many other companies each person currently directs, shown next to an appointment or exit.
    private function withOtherBoards(Collection $rows): Collection
    {
        $dins = $rows->pluck('din')->filter()->unique();
        $boards = $dins->isEmpty() ? collect() : DB::table('company_officers')->whereIn('din', $dins)->whereNull('ceased_on')
            ->distinct()->get(['din', 'company_id'])->groupBy('din')->map(fn ($g) => $g->pluck('company_id'));
        return $rows->each(fn ($r) => $r->other_boards = $r->din ? ($boards[$r->din] ?? collect())->reject(fn ($id) => $id == $r->company_id)->count() : null);
    }
}
