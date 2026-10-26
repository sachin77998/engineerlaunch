<?php

namespace App\Services\Approvals\Drivers;

/**
 * Invest Meghalaya EoDB pages: the services tables (department, SOP, rules, enclosures, form, apply link), the
 * pre-establishment / pre-operation document checklists (document, whom to apply, remark) and linked policies.
 */
class InvestMeghalaya extends PortalDriver
{
    private const PAGES = ['services.html', 'otherservices.html'];
    private const STAGE_PAGES = ['checklistforPreEstablishment.html' => 'Pre-Establishment', 'checklistforPreOperation.html' => 'Pre-Operation'];

    private array $rules = [];

    private function dir(): string
    {
        return rtrim(preg_replace('~[^/]*$~', '', $this->source->services_url), '/') . '/';
    }

    public function services(): array
    {
        $out = [];
        foreach (self::PAGES as $file) {
            $page = $this->dir() . $file;
            $x = $this->dom($this->get($page));
            foreach ($x->query('//table[@id="servicestable"]/tbody/tr') as $tr) {
                $td = iterator_to_array($x->query('td', $tr));
                if (count($td) < 7) continue;
                $name = self::clean(self::inner($td[0]));
                if ($name === '') continue;
                $link = fn ($cell) => ($a = $x->query('.//a[@href and not(starts-with(@href, "#"))]', $cell)->item(0)) ? $this->abs($a->getAttribute('href'), $page) : null;
                $content = $x->query('.//div[contains(@class, "content")]', $td[4])->item(0);
                $apply = $link($td[6]);
                $id = $apply && preg_match('~serviceId=(\d+)~', $apply, $m) ? 'S' . $m[1] : 'N' . substr(md5(strtolower($name)), 0, 10);
                if ($rule = $link($td[3])) $this->rules[$rule] = $name;
                $out[] = [
                    // The portal reuses a serviceId across rows (e.g. factory licence and building permission), so the name keeps each unique.
                    'external_id' => $id . '-' . substr(md5(strtolower($name)), 0, 6),
                    'department' => self::clean(self::inner($td[1])) ?: null,
                    'name' => $name,
                    'service_type' => null, 'stages' => [], 'integration' => $apply ? 'Online on Invest Meghalaya' : null,
                    'checklist_text' => $content ? $this->enclosures(self::lines(preg_replace('~<a\b[^>]*>.*?</a>~is', '', self::inner($content)))) : null,
                    'sop_url' => $link($td[2]), 'form_url' => $link($td[5]), 'apply_url' => $apply,
                    'checklist_url' => null, 'fee_url' => null, 'timeline_url' => null, 'inspection_url' => null,
                    'risk_categories' => [], 'unit_sizes' => [], 'locations' => [], 'investor_types' => [],
                ];
            }
        }
        foreach (self::STAGE_PAGES as $file => $stage) {
            $page = $this->dir() . $file;
            $x = $this->dom($this->get($page));
            $docs = [];
            foreach ($x->query('//table//tr[td]') as $tr) {
                $td = iterator_to_array($x->query('td', $tr));
                if (count($td) < 3 || !preg_match('~^\d+~', trim($td[0]->textContent))) continue;
                $docs[] = ['code' => null, 'doc_type' => null, 'issued_by' => mb_substr(self::clean(self::inner($td[2])), 0, 255) ?: null,
                    'name' => self::clean(self::inner($td[1])), 'is_required' => null, 'condition' => isset($td[3]) ? (mb_substr(self::clean(self::inner($td[3])), 0, 500) ?: null) : null];
            }
            if ($docs) $out[] = [
                'external_id' => 'STAGE' . ($stage === 'Pre-Establishment' ? 1 : 2) . '-0',
                'department' => 'Invest Meghalaya Authority', 'name' => "Documents required at the {$stage} stage",
                'service_type' => 'Checklist', 'stages' => [$stage], 'integration' => null, 'documents' => $docs,
                'checklist_url' => null, 'form_url' => null, 'fee_url' => null, 'sop_url' => null, 'timeline_url' => null, 'inspection_url' => null, 'apply_url' => null,
                'risk_categories' => [], 'unit_sizes' => [], 'locations' => [], 'investor_types' => [],
            ];
        }
        return $out;
    }

    /** "Mandatory" / "Optional" headings and "- Mandatory" suffixes become the parser's (M) / if-applicable markers. */
    private function enclosures(string $text): ?string
    {
        $out = []; $mode = null;
        foreach (preg_split('~\R~u', preg_replace('~^\s*Enclosures\s*~i', '', $text)) as $line) {
            if (preg_match('~^(Mandatory|Optional|Non[- ]?Mandatory)\s*(documents?)?\s*:?\s*(.*)$~i', $line, $m)) {
                $mode = stripos($m[1], 'mandatory') === 0 ? 'M' : 'O';
                if (trim($m[3]) === '') continue;
                $line = $m[3];
            }
            $line = preg_replace(['~\s*[-–]\s*Mandatory\b\.?~i', '~\s*\(Mandatory\)~i', '~\s*[-–]\s*Optional\b\.?~i'], [' (M)', ' (M)', ' (if applicable)'], $line);
            if ($mode && preg_match('~^\d+[.)]~', $line) && !preg_match('~\((M|if applicable)\)~i', $line)) $line .= $mode === 'M' ? ' (M)' : ' (if applicable)';
            $out[] = $line;
        }
        $text = trim(implode("\n", $out));
        return $text === '' ? null : $text;
    }

    public function policies(): array
    {
        $out = [];
        $home = $this->source->policies_url ?: $this->source->portal_url;
        $html = $this->get($home);
        foreach ($this->policyLinks($html, $this->landed ?: $home, '~/policies/|polic~i') as $url => $title) {
            $out[] = ['kind' => 'policy', 'title' => $title, 'department' => null, 'url' => $url, 'language' => null];
        }
        foreach ($this->rules as $url => $service) {
            $out[] = ['kind' => 'rule', 'title' => 'Rules for ' . $service, 'department' => null, 'url' => $url, 'language' => null];
        }
        return $out;
    }
}
