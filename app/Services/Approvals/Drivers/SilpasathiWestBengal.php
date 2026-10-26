<?php

namespace App\Services\Approvals\Drivers;

/**
 * West Bengal Silpasathi "Comprehensive Information Sheet": one tab set per department with each service's standard
 * operating procedure, timeline and document checklist as accordion panels. The portal does not publish stages or
 * applicability filters, so those stay empty.
 */
class SilpasathiWestBengal extends PortalDriver
{
    public function services(): array
    {
        $x = $this->dom($this->get($this->source->services_url));
        $out = [];
        foreach ($x->query('//li[contains(concat(" ", normalize-space(@class), " "), " directoartename ")]') as $li) {
            $id = $li->getAttribute('directoarteid'); // the HTML parser lower-cases attribute names
            $dept = trim(preg_replace('~\s+~u', ' ', $li->textContent));
            if ($id === '' || $dept === '') continue;
            $checklists = $this->panels($x, 'checklists' . $id);
            $timelines = $this->panels($x, 'timeline' . $id);
            $sops = $this->panels($x, 'sop' . $id);
            foreach (array_unique(array_merge(array_keys($checklists), array_keys($timelines), array_keys($sops))) as $name) {
                $out[] = [
                    'external_id' => $id . '-' . substr(md5(strtolower($name)), 0, 8),
                    'department' => $dept,
                    'name' => $name,
                    'service_type' => null,
                    'stages' => [],
                    'integration' => 'Online on Silpasathi',
                    // Upload-size notes ("(Allowed Maximum file size-200 KB, pdf Only)") are portal form hints, not part of the document name.
                    'checklist_text' => isset($checklists[$name]) ? preg_replace('~\s*\(\s*Allowed Maximum file size[^)]*\)~i', '', self::lines($checklists[$name])) : null,
                    'timeline' => isset($timelines[$name]) ? mb_substr(self::clean($timelines[$name]), 0, 255) : null,
                    'procedure' => isset($sops[$name]) ? self::lines($sops[$name]) : null,
                    'apply_url' => $this->source->portal_url,
                    'checklist_url' => null, 'form_url' => null, 'fee_url' => null, 'sop_url' => null, 'timeline_url' => null, 'inspection_url' => null,
                    'risk_categories' => [], 'unit_sizes' => [], 'locations' => [], 'investor_types' => [],
                ];
            }
        }
        return $out;
    }

    /** @return array<string, string> service name => panel body HTML */
    private function panels(\DOMXPath $x, string $paneId): array
    {
        $out = [];
        foreach ($x->query('//div[@id="' . $paneId . '"]//div[contains(@class, "panel-heading")]') as $head) {
            $name = trim(preg_replace(['~^\s*\d+\)\s*~', '~\s+~u'], ['', ' '], $head->textContent));
            $body = $x->query('following-sibling::div[1]//div[contains(@class, "panel-body")]', $head)->item(0);
            if ($name !== '' && $body) $out[$name] = self::inner($body);
        }
        return $out;
    }
}
