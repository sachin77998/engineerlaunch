<?php

namespace App\Services\Approvals\Drivers;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

/**
 * National Single Window System (nsws.gov.in): the state's approvals from the public marketplace list, each with its
 * public license detail (required enclosures, stage, timeline, fee type, acts and rules). Only public, anonymous
 * endpoints the NSWS approval pages call themselves are used.
 */
class NswsState extends PortalDriver
{
    private const API = 'https://www.nsws.gov.in/gateway-sec/form-builder-sec/';
    private const STAGES = ['PRE_ESTABLISHMENT' => 'Pre-Establishment', 'PRE_OPERATIONS' => 'Pre-Operation', 'OPERATION' => 'Operation / Renewal', 'POST_OPERATIONS' => 'Post-Operation'];

    private array $acts = [];

    private function http()
    {
        return Http::withHeaders(['User-Agent' => self::UA, 'Accept' => 'application/json'])->timeout(90)->retry(2, 2000);
    }

    public function services(): array
    {
        $name = DB::table('industrial_states')->where('id', $this->source->state_id)->value('name');
        $stateId = $this->http()->post(self::API . 'state/fetch-by-name', ['name' => $name])->throw()->json('data.id');
        if (!$stateId) return [];
        $list = $this->http()->post(self::API . 'marketplace/state-license-summary-list', ['start' => 0, 'length' => 5000, 'searchTag' => '', 'filters' => ['state_id' => [$stateId], 'authority_id' => []]])->throw()->json('data') ?? [];
        $list = array_values(array_filter($list, fn ($l) => ($l['publish'] ?? true) && !empty($l['urlTitle'])));
        // Some approvals carry no authority slug (seen in Goa / Karnataka); their detail page cannot be addressed, so the
        // listing alone is kept.
        $addressable = array_values(array_filter($list, fn ($l) => !empty($l['authorityUrlTitle']) && !empty($l['stateUrlTitle'])));

        $details = [];
        foreach (array_chunk($addressable, 4) as $chunk) {
            $responses = Http::pool(fn (Pool $pool) => array_map(fn ($l) => $pool->as((string) $l['licenseId'])->withHeaders(['User-Agent' => self::UA, 'Accept' => 'application/json'])->timeout(60)
                ->get(self::API . 'marketplace//license-detail', ['licenseUrlTitle' => $l['urlTitle'], 'stateUrlTitle' => $l['stateUrlTitle'], 'authorityUrlTitle' => $l['authorityUrlTitle']]), $chunk));
            foreach ($responses as $key => $res) {
                if ($res instanceof \Illuminate\Http\Client\Response && $res->successful()) $details[$key] = $res->json('data');
            }
            usleep((int) config('state_approvals.request_gap_ms', 250) * 1000);
        }

        $out = [];
        foreach ($list as $l) {
            $d = $details[(string) $l['licenseId']] ?? [];
            $page = !empty($l['authorityUrlTitle']) && !empty($l['stateUrlTitle']) ? 'https://www.nsws.gov.in/portal/approval-details/' . $l['stateUrlTitle'] . '/' . $l['authorityUrlTitle'] . '/' . $l['urlTitle'] : null;
            foreach ($d['actAndRules'] ?? [] as $a) {
                $title = self::clean((string) ($a['name'] ?? ''));
                if ($title !== '' && !empty($a['link']) && preg_match('~^https?://~i', $a['link'])) $this->acts[$a['link']] = [$title, $a['type'] ?? 'Act', $l['authorityName'] ?? null];
            }
            $timeline = trim((string) ($d['timeline'] ?? ''));
            $out[] = [
                'external_id' => 'NS' . $l['licenseId'] . '-0',
                'department' => $l['authorityName'] ?? null,
                'name' => trim($l['licenseName']),
                'service_type' => isset($l['approvalType']) ? ucfirst(strtolower(str_replace('_', ' ', $l['approvalType']))) : null,
                'stages' => isset($d['stage'], self::STAGES[$d['stage']]) ? [self::STAGES[$d['stage']]] : [],
                'integration' => !empty($d['applyFromNsws']) ? 'Apply on NSWS' : (!empty($l['availableOnSws']) ? 'Apply on the state single window' : null),
                'checklist_text' => !empty($d['requiredEnclosures']) ? $this->enclosures($d['requiredEnclosures']) : null,
                'timeline' => $timeline !== '' && stripos($timeline, 'not available') === false ? $timeline : null,
                'fee' => $this->fee($d + $l),
                'description' => self::clean((string) ($d['aboutLicense'] ?? $l['details'] ?? '')) ?: null,
                'apply_url' => $page,
                'checklist_url' => null, 'form_url' => null, 'fee_url' => null, 'sop_url' => null, 'timeline_url' => null, 'inspection_url' => null,
                'risk_categories' => [], 'unit_sizes' => [], 'locations' => [], 'investor_types' => [],
            ];
        }
        return $out;
    }

    /** NSWS enclosures are one document per list item / paragraph: unnumbered lines become bullets for the parser. */
    private function enclosures(string $html): ?string
    {
        $lines = array_filter(preg_split('~\R~u', self::lines($html)), fn ($l) => trim($l, " \t.-") !== '');
        $out = array_map(fn ($l) => preg_match('~^(\(?\d{1,3}[.)]|\(?[a-z][.)]\s|[\x{2022}\x{F0B7}*-])~u', $l) || preg_match('~:$~', $l) ? $l : "\u{2022} " . $l, $lines);
        return $out ? implode("\n", $out) : null;
    }

    private function fee(array $d): ?string
    {
        if (!empty($d['variable'])) return 'Varies with the application';
        return match ($d['paymentType'] ?? null) {
            'FREE' => 'No fee',
            'PAID' => ($d['price'] ?? 0) > 0 ? '₹' . number_format((float) $d['price'], 0) : 'Fee as notified by the department',
            default => null,
        };
    }

    public function policies(): array
    {
        $out = [];
        foreach ($this->acts as $url => [$title, $type, $dept]) {
            $out[] = ['kind' => preg_match('~rule~i', $type . ' ' . $title) ? 'rule' : (preg_match('~notif|order|circular~i', $type) ? 'notification' : (preg_match('~polic~i', $type . ' ' . $title) ? 'policy' : 'act')),
                'title' => mb_substr($title, 0, 600), 'department' => $dept, 'url' => $url, 'language' => null];
        }
        return $out;
    }
}
