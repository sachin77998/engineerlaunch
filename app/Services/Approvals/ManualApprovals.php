<?php

namespace App\Services\Approvals;

use Illuminate\Support\Facades\DB;

// Writes the documents, notes and processing steps taken from departments' user manuals (config/state_approval_manuals.php)
// as source 'manual'. The portal importer never touches that source, and services added here are locked against
// deactivation. Safe to run repeatedly: each service's manual rows are replaced.
class ManualApprovals
{
    /** @return int services updated */
    public function apply(): int
    {
        $n = 0;
        foreach (config('state_approval_manuals', []) as $stateName => $cfg) {
            $stateId = DB::table('industrial_states')->where('name', $stateName)->value('id');
            if (!$stateId) continue;
            foreach ($cfg['services'] as $s) {
                $service = DB::table('state_approval_services')->where('state_id', $stateId)->where('external_id', $s['external_id'])->first();
                if (!$service && empty($s['new'])) continue;
                if (!$service) {
                    $id = DB::table('state_approval_services')->insertGetId(['state_id' => $stateId, 'external_id' => $s['external_id'], 'department' => $s['department'],
                        'name' => $s['name'], 'service_type' => $s['service_type'] ?? null, 'stages' => json_encode($s['stages'] ?? []), 'locked' => true, 'is_active' => true,
                        'created_at' => now(), 'updated_at' => now()]);
                } else $id = $service->id;
                if (!empty($s['sop_url'])) DB::table('state_approval_services')->where('id', $id)->update(['sop_url' => $s['sop_url'], 'updated_at' => now()]);
                $rows = []; $pos = 0;
                foreach ($s['documents'] ?? [] as $d) {
                    $rows[] = ['kind' => 'document', 'name' => $d[0], 'is_required' => $d[1], 'condition' => $d[2] ?? null, 'group_label' => null];
                }
                foreach ($s['notes'] ?? [] as $note) $rows[] = ['kind' => 'note', 'name' => $note, 'is_required' => null, 'condition' => null, 'group_label' => null];
                foreach ($s['steps'] ?? [] as [$actor, $text]) $rows[] = ['kind' => 'step', 'name' => $text, 'is_required' => null, 'condition' => null, 'group_label' => $actor];
                DB::transaction(function () use ($id, $rows, &$pos) {
                    DB::table('state_approval_documents')->where('service_id', $id)->where('source', 'manual')->delete();
                    DB::table('state_approval_documents')->insert(array_map(fn ($r) => $r + ['service_id' => $id, 'source' => 'manual', 'position' => $pos++,
                        'created_at' => now(), 'updated_at' => now()], $rows));
                });
                $n++;
            }
        }
        return $n;
    }
}
