<?php

namespace App\Services\Approvals;

use App\Services\Approvals\Drivers\SwcsUttarakhand;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Reads each state's single-window portal into state_approval_services / _documents and state_policies. Checklist PDFs
 * are parsed only when their content changes; services the owner has locked keep their edited documents.
 */
class StateApprovalImporter
{
    public const DRIVERS = [
        'swcs_uk' => SwcsUttarakhand::class,
        'silpasathi_wb' => Drivers\SilpasathiWestBengal::class,
        'meghalaya' => Drivers\InvestMeghalaya::class,
        'policy_pages' => Drivers\PolicyPages::class,
    ];

    public function __construct(private PdfText $pdf, private ChecklistParser $parser) {}

    /** Adds configured states missing from the sources table (owner edits to existing rows are kept). */
    public function seedSources(): int
    {
        $n = 0;
        $states = DB::table('industrial_states')->pluck('id', 'code');
        foreach (config('state_approvals.sources') as $code => [$name, $url, $driver, $urls]) {
            $stateId = $states[$code] ?? null;
            if (!$stateId) continue;
            $existing = DB::table('state_approval_sources')->where('state_id', $stateId)->first();
            if ($existing) {
                // A reader added in a later release fills a link-only row; addresses the owner already set are kept.
                if (!$existing->driver && $driver) {
                    DB::table('state_approval_sources')->where('id', $existing->id)->update(['driver' => $driver, 'services_url' => $existing->services_url ?: ($urls['services_url'] ?? null),
                        'policies_url' => $existing->policies_url ?: ($urls['policies_url'] ?? null), 'acts_url' => $existing->acts_url ?: ($urls['acts_url'] ?? null), 'updated_at' => now()]);
                    $n++;
                }
                continue;
            }
            DB::table('state_approval_sources')->insert(['state_id' => $stateId, 'portal_name' => $name, 'portal_url' => $url, 'driver' => $driver,
                'services_url' => $urls['services_url'] ?? null, 'policies_url' => $urls['policies_url'] ?? null, 'acts_url' => $urls['acts_url'] ?? null,
                'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
            $n++;
        }
        return $n;
    }

    /** @return array{services: int, documents: int, checklists: int, policies: int, status: string} */
    public function run(object $source, ?callable $log = null, ?int $limit = null): array
    {
        $log ??= fn () => null;
        $out = ['services' => 0, 'documents' => 0, 'checklists' => 0, 'policies' => 0, 'status' => ''];
        $class = self::DRIVERS[$source->driver] ?? null;
        if (!$class) {
            $out['status'] = 'No reader for this portal yet; visitors are linked to it';
            DB::table('state_approval_sources')->where('id', $source->id)->update(['last_status' => $out['status'], 'last_fetched_at' => now()]);
            return $out;
        }
        $driver = new $class($source);
        $gap = (int) config('state_approvals.request_gap_ms', 250) * 1000;
        try {
            $services = $driver->services();
            if (!$services && $source->services_url) throw new \RuntimeException('The service list came back empty');
            $seen = [];
            foreach ($limit ? array_slice($services, 0, $limit) : $services as $s) {
                $seen[] = $s['external_id'];
                $row = collect($s)->only(['department', 'name', 'service_type', 'integration', 'checklist_url', 'form_url', 'fee_url', 'sop_url', 'timeline_url', 'inspection_url', 'timeline', 'apply_url'])
                    ->map(fn ($v, $k) => is_string($v) ? mb_substr($v, 0, $k === 'name' ? 600 : ($k === 'department' || $k === 'timeline' ? 255 : 500)) : $v)->all();
                if (array_key_exists('procedure', $s)) $row['procedure'] = $s['procedure'] ? mb_substr($s['procedure'], 0, 20000) : null;
                foreach (['stages', 'risk_categories', 'unit_sizes', 'locations', 'investor_types'] as $k) $row[$k] = json_encode($s[$k]);
                $existing = DB::table('state_approval_services')->where('state_id', $source->state_id)->where('external_id', $s['external_id'])->first();
                if ($existing) {
                    DB::table('state_approval_services')->where('id', $existing->id)->update($row + ['fetched_at' => now(), 'updated_at' => now()]);
                    $id = $existing->id;
                } else {
                    $id = DB::table('state_approval_services')->insertGetId($row + ['state_id' => $source->state_id, 'external_id' => $s['external_id'], 'is_active' => true, 'fetched_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                }
                $out['services']++;
                if ($existing?->locked) continue;

                try {
                    $docs = $driver->documents($s);
                    $this->replace($id, 'portal', $docs);
                    $out['documents'] += count($docs);
                } catch (Throwable $e) {
                    $log("  {$s['external_id']}: documents not read — " . mb_substr($e->getMessage(), 0, 120));
                    $docs = [];
                }
                if (!isset($s['documents'])) usleep($gap);

                // Checklist published as page text: parsed whenever the text changes.
                if (!empty($s['checklist_text'])) {
                    $hash = hash('sha256', $s['checklist_text']);
                    if ($hash !== $existing?->checklist_hash) {
                        $items = $this->parser->parse($s['checklist_text'], $docs);
                        $this->replace($id, 'checklist', $items);
                        DB::table('state_approval_services')->where('id', $id)->update(['checklist_hash' => $hash, 'checklist_text' => $s['checklist_text']]);
                        $out['checklists']++;
                        $log("  {$s['external_id']} {$s['name']}: " . count($items) . ' checklist item(s)');
                    }
                } elseif (!empty($s['checklist_url'])) {
                    $bin = $driver->pdf($s['checklist_url']);
                    usleep($gap);
                    $hash = $bin ? hash('sha256', $bin) : null;
                    if ($hash && $hash !== $existing?->checklist_hash) {
                        try {
                            $text = implode("\n\f\n", $this->pdf->pages($bin));
                            $items = $text !== '' ? $this->parser->parse($text, $docs) : [];
                            $this->replace($id, 'checklist', $items);
                            DB::table('state_approval_services')->where('id', $id)->update(['checklist_hash' => $hash, 'checklist_text' => $text ?: null]);
                            $out['checklists']++;
                            $log("  {$s['external_id']} {$s['name']}: " . count($items) . ' checklist item(s)' . ($text === '' ? ' (PDF has no text layer)' : ''));
                        } catch (Throwable $e) {
                            $log("  {$s['external_id']}: checklist not parsed — " . mb_substr($e->getMessage(), 0, 120));
                        }
                    }
                }
            }
            if (!$limit && $seen) {
                DB::table('state_approval_services')->where('state_id', $source->state_id)->whereNotIn('external_id', $seen)->where('locked', false)->update(['is_active' => false, 'updated_at' => now()]);
            }
            $policies = $driver->policies();
            $hashes = array_map(fn ($p) => sha1($p['url']), $policies);
            if ($hashes) {
                // Links no longer on the portal are hidden (an empty read is treated as a fetch problem, not a removal).
                DB::table('state_policies')->where('state_id', $source->state_id)->whereNotIn('url_hash', $hashes)->update(['is_active' => false, 'updated_at' => now()]);
            }
            foreach ($policies as $p) {
                $hash = sha1($p['url']);
                $row = ['state_id' => $source->state_id, 'kind' => $p['kind'], 'title' => mb_substr($p['title'], 0, 600), 'department' => $p['department'] ? mb_substr($p['department'], 0, 255) : null, 'url' => mb_substr($p['url'], 0, 500), 'language' => $p['language'], 'is_active' => true, 'updated_at' => now()];
                if (DB::table('state_policies')->where('url_hash', $hash)->exists()) DB::table('state_policies')->where('url_hash', $hash)->update($row);
                else DB::table('state_policies')->insert($row + ['url_hash' => $hash, 'created_at' => now()]);
                $out['policies']++;
            }
            $count = DB::table('state_approval_services')->where('state_id', $source->state_id)->where('is_active', true)->count();
            $out['status'] = "{$out['services']} services read, {$out['checklists']} checklist(s) parsed, {$out['policies']} policy / act files";
            DB::table('state_approval_sources')->where('id', $source->id)->update(['services_count' => $count, 'last_status' => mb_substr($out['status'], 0, 400), 'last_fetched_at' => now(), 'updated_at' => now()]);
        } catch (Throwable $e) {
            $out['status'] = 'Error: ' . mb_substr($e->getMessage(), 0, 300);
            DB::table('state_approval_sources')->where('id', $source->id)->update(['last_status' => $out['status'], 'last_fetched_at' => now()]);
        }
        return $out;
    }

    /** Re-parses stored checklist text with the current parser (no downloads); locked services are skipped. */
    public function reparse(?int $stateId = null): int
    {
        $n = 0;
        $services = DB::table('state_approval_services')->whereNotNull('checklist_text')->where('locked', false)->when($stateId, fn ($q) => $q->where('state_id', $stateId))->get(['id', 'checklist_text']);
        foreach ($services as $s) {
            $docs = DB::table('state_approval_documents')->where('service_id', $s->id)->where('source', 'portal')->get(['name', 'code'])->map(fn ($d) => (array) $d)->all();
            $this->replace($s->id, 'checklist', $this->parser->parse($s->checklist_text, $docs));
            $n++;
        }
        return $n;
    }

    private function replace(int $serviceId, string $source, array $items): void
    {
        DB::transaction(function () use ($serviceId, $source, $items) {
            DB::table('state_approval_documents')->where('service_id', $serviceId)->where('source', $source)->delete();
            $rows = [];
            foreach (array_values($items) as $i => $it) {
                $rows[] = ['service_id' => $serviceId, 'source' => $source, 'position' => $i + 1, 'kind' => $it['kind'] ?? 'document', 'code' => $it['code'] ?? null,
                    'doc_type' => isset($it['doc_type']) ? mb_substr($it['doc_type'], 0, 255) : null, 'issued_by' => isset($it['issued_by']) ? mb_substr($it['issued_by'], 0, 255) : null,
                    'name' => $it['name'], 'condition' => isset($it['condition']) ? mb_substr($it['condition'], 0, 500) : null, 'group_label' => isset($it['group_label']) ? mb_substr($it['group_label'], 0, 500) : null,
                    'is_required' => $it['is_required'] ?? null, 'created_at' => now(), 'updated_at' => now()];
            }
            foreach (array_chunk($rows, 200) as $chunk) DB::table('state_approval_documents')->insert($chunk);
        });
    }
}
