<?php

namespace App\Http\Controllers;

use App\Models\Job;
use App\Services\Recommendations\JobRecommender;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Recommended for you": jobs ranked from what this visitor searched, viewed, applied to or told the assistant.
 * Anonymous visitors send their recent signals from the browser (localStorage); signed-in candidates also get their
 * stored history, so recommendations follow them across devices.
 */
class RecommendationController extends Controller
{
    public function jobs(Request $request, JobRecommender $recommender)
    {
        $data = $request->validate([
            'signals' => 'nullable|string|max:8000', 'job' => 'nullable|integer', 'limit' => 'nullable|integer|min:1|max:20',
            'experience' => 'nullable|integer|min:0|max:50', 'location' => 'nullable|string|max:80',
        ]);
        $signals = collect(json_decode($data['signals'] ?? '[]', true) ?: [])
            ->filter(fn ($s) => is_array($s) && in_array($s['kind'] ?? null, ['search', 'view', 'apply', 'assistant'], true) && is_string($s['value'] ?? null))
            ->map(fn ($s) => ['kind' => $s['kind'], 'value' => mb_substr(trim($s['value']), 0, 190), 'at' => (int) ($s['at'] ?? time()), 'job' => isset($s['job']) ? (int) $s['job'] : null])
            ->filter(fn ($s) => $s['value'] !== '')->take(-40)->values()->all();

        $exclude = [];
        $user = $request->user();
        if ($user && Schema::hasTable('interest_events')) {
            $this->remember($user->id, $signals);
            $stored = DB::table('interest_events')->where('user_id', $user->id)->latest('id')->limit(60)->get()
                ->map(fn ($e) => ['kind' => $e->kind, 'value' => $e->value, 'at' => strtotime($e->created_at), 'job' => $e->job_id])->reverse()->values()->all();
            $signals = array_merge($stored, $signals);
            $exclude = DB::table('job_applications')->where('user_id', $user->id)->where('status', '!=', 'withdrawn')->pluck('job_id')->all();
        }
        if (!empty($data['job']) && ($job = Job::find($data['job']))) {
            $signals[] = ['kind' => 'view', 'value' => $job->title, 'at' => time(), 'job' => $job->id];
            $exclude[] = $job->id;
        }
        if (!$signals) return response()->json(['jobs' => [], 'because' => []]);

        $result = $recommender->recommend($signals, [
            'experience' => $data['experience'] ?? null, 'location' => $data['location'] ?? null,
            'exclude' => array_values(array_unique($exclude)), 'limit' => $data['limit'] ?? 8,
        ]);
        return response()->json([
            'because' => array_keys($result['profile']),
            'related' => array_keys(array_slice($result['related'], 0, 4, true)),
            'jobs' => $result['jobs']->map(fn ($row) => JobRecommender::present($row))->values(),
        ]);
    }

    /** Stores new signals for a signed-in candidate, skipping repeats from the last day. */
    private function remember(int $userId, array $signals): void
    {
        $recent = DB::table('interest_events')->where('user_id', $userId)->where('created_at', '>=', now()->subDay())
            ->get(['kind', 'value'])->map(fn ($e) => $e->kind . '|' . mb_strtolower($e->value))->flip();
        $rows = [];
        foreach ($signals as $signal) {
            $key = $signal['kind'] . '|' . mb_strtolower($signal['value']);
            if ($recent->has($key)) continue;
            $recent[$key] = true;
            $rows[] = ['user_id' => $userId, 'kind' => $signal['kind'], 'value' => $signal['value'], 'job_id' => $signal['job'], 'created_at' => now()];
        }
        if ($rows) DB::table('interest_events')->insert($rows);
    }
}
