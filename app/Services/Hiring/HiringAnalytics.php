<?php

namespace App\Services\Hiring;

use App\Models\HiringCostSetting;
use App\Models\JobApplication;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HiringAnalytics
{
    public function build(User $user, Carbon $from, Carbon $to): array
    {
        $team = app(Team::class)->userIds($user);
        $scope = fn () => DB::table('job_applications as a')->join('jobs as j', 'j.id', '=', 'a.job_id')->when($user->role !== 'admin', fn ($q) => $q->whereIn('j.employer_id', $team));
        $inPeriod = fn () => $scope()->whereBetween('a.applied_at', [$from, $to]);

        $received = $inPeriod()->count();
        $stages = $inPeriod()->select('a.stage', DB::raw('count(*) c'))->groupBy('a.stage')->pluck('c', 'stage');
        $autoScreened = $inPeriod()->whereNotNull('a.screened_at')->count();
        $autoRejected = $inPeriod()->where('a.rejection_reason', 'like', 'Auto-screen%')->count();
        $autoShortlisted = $inPeriod()->whereExists(fn ($e) => $e->selectRaw(1)->from('application_status_history as h')->whereColumn('h.job_application_id', 'a.id')->whereNull('h.changed_by')->where('h.note', 'like', 'Stage: Shortlisted — Auto-screen%'))->count();
        $hires = $scope()->where('a.stage', 'hired')->whereBetween('a.stage_changed_at', [$from, $to]);
        $hireCount = (clone $hires)->count();
        $daysToHire = (clone $hires)->whereNotNull('a.applied_at')->avg(DB::raw('TIMESTAMPDIFF(HOUR, a.applied_at, a.stage_changed_at) / 24'));
        $firstAction = $inPeriod()->join('application_status_history as h', 'h.job_application_id', '=', 'a.id')->whereNotNull('h.changed_by')->whereColumn('h.changed_by', '!=', 'a.user_id')
            ->selectRaw('a.id, MIN(h.created_at) first_at, a.applied_at')->groupBy('a.id', 'a.applied_at');
        $daysToAction = DB::query()->fromSub($firstAction, 'x')->avg(DB::raw('TIMESTAMPDIFF(HOUR, x.applied_at, x.first_at) / 24'));
        $waiting = $scope()->where('a.stage', 'new')->where('a.applied_at', '<', now()->subDays(3))->count();

        $interviews = DB::table('hiring_interviews as i')->join('job_applications as a', 'a.id', '=', 'i.job_application_id')->join('jobs as j', 'j.id', '=', 'a.job_id')
            ->when($user->role !== 'admin', fn ($q) => $q->whereIn('j.employer_id', $team))->whereBetween('i.scheduled_at', [$from, $to])
            ->select('i.status', DB::raw('count(*) c'))->groupBy('i.status')->pluck('c', 'status');

        $byJob = $inPeriod()->groupBy('j.id', 'j.title', 'j.status')->orderByDesc(DB::raw('count(*)'))->limit(25)->get([
            'j.id', 'j.title', 'j.status', DB::raw('count(*) apps'), DB::raw("sum(a.stage in ('shortlisted','interview','offered','hired')) shortlisted"),
            DB::raw("sum(a.stage in ('interview','offered','hired')) interviewed"), DB::raw("sum(a.stage = 'hired') hired"), DB::raw('round(avg(a.match_score)) score')]);
        $bySource = $inPeriod()->groupBy(DB::raw("coalesce(a.application_source,'portal')"))->get([DB::raw("coalesce(a.application_source,'portal') source"), DB::raw('count(*) apps'), DB::raw("sum(a.stage = 'hired') hired")]);
        $trend = $scope()->where('a.applied_at', '>=', now()->subMonths(11)->startOfMonth())->groupBy(DB::raw("DATE_FORMAT(a.applied_at,'%Y-%m')"))
            ->get([DB::raw("DATE_FORMAT(a.applied_at,'%Y-%m') m"), DB::raw('count(*) apps'), DB::raw("sum(a.stage = 'hired') hired")])->keyBy('m');
        $months = collect(range(11, 0))->map(fn ($i) => now()->subMonths($i)->format('Y-m'))->mapWithKeys(fn ($m) => [$m => ['apps' => (int) ($trend[$m]->apps ?? 0), 'hired' => (int) ($trend[$m]->hired ?? 0)]]);

        // Cost model from the employer's own numbers: spend over the period divided by hires actually made here.
        $cost = HiringCostSetting::firstOrNew(['user_id' => app(Team::class)->ownerId($user)]);
        $periodMonths = max(1 / 30, $from->diffInDays($to) / 30);
        $spend = (($cost->recruiters ?? 1) * $cost->monthly_cost_per_recruiter + $cost->monthly_tools_spend + $cost->monthly_job_board_spend) * $periodMonths;
        $hoursSaved = $autoScreened * ($cost->minutes_per_manual_screen ?: 6) / 60;
        $recruiterHours = 8 * 22 * $periodMonths;
        return [
            'received' => $received, 'stages' => $stages, 'auto_screened' => $autoScreened, 'auto_rejected' => $autoRejected, 'auto_shortlisted' => $autoShortlisted,
            'hires' => $hireCount, 'days_to_hire' => $daysToHire !== null ? round((float) $daysToHire, 1) : null, 'days_to_action' => $daysToAction !== null ? round((float) $daysToAction, 1) : null,
            'waiting' => $waiting, 'interviews' => $interviews, 'by_job' => $byJob, 'by_source' => $bySource, 'months' => $months,
            'cost' => $cost, 'spend' => round($spend), 'cost_per_hire' => $hireCount ? round($spend / $hireCount) : null,
            'agency_cost' => $hireCount * $cost->agency_fee_per_hire, 'hours_saved' => round($hoursSaved, 1),
            'recruiters_freed' => $recruiterHours ? round($hoursSaved / $recruiterHours, 2) : 0,
            'money_saved' => round($hoursSaved / max(1, $recruiterHours) * $cost->monthly_cost_per_recruiter * $periodMonths),
            'conversion' => $received ? round($hireCount / $received * 100, 1) : null,
            'stage_labels' => JobApplication::STAGES,
        ];
    }
}
