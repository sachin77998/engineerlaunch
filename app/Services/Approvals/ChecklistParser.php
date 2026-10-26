<?php

namespace App\Services\Approvals;

/**
 * Turns a checklist PDF's text into items. Handles numbered lists, bullet lists ("any of the following" bullets become
 * alternatives of their parent item), section headers ("In case of Company:"), "(M)" mandatory markers,
 * "# / Document Name / Condition / Mandatory" tables, coded document tables (UK-DCL-n rows with check points) and
 * two-column department NOC grids.
 */
class ChecklistParser
{
    private const BULLET = '~^[\x{2022}\x{F0B7}\x{F0A7}\x{F076}\x{25AA}\x{25CF}\x{25E6}\x{2219}\x{27A2}\x{F0D8}\x{F0FC}*]\s*(.+)$~u';
    private const CONDITION = '~\s*[,(]?\s*\(?\b((?:only\s+)?(?:if|where|in case|as|wherever|for)\s+(?:applicable|any|required|case to case basis|applicable,\s*case to case basis)[^)]*|only for [^)]+)\)?\.?\s*$~i';
    private const SHORT_WORDS = ['a', 'an', 'as', 'at', 'be', 'by', 'do', 'if', 'in', 'is', 'it', 'no', 'of', 'on', 'or', 'to', 'up', 'and', 'any', 'are', 'for', 'its', 'not', 'the', 'per', 'all', 'one', 'two', 'new', 'old', 'own', 'from', 'with', 'last', 'fee', 'fees', 'copy', 'form', 'land', 'name', 'plan', 'site', 'unit', 'year', 'self', 'bill', 'deed', 'card', 'list', 'map', 'maps', 'test', 'tax', 'type', 'yes'];

    /**
     * @param array<int, array{name: string, code?: ?string}> $portalDocs documents the portal maps to this service (names split
     *        table rows into name / condition; codes match rows of coded document tables)
     * @return array<int, array{kind: string, name: string, condition: ?string, group_label: ?string, is_required: ?bool}>
     */
    public function parse(string $text, array $portalDocs = []): array
    {
        $knownNames = array_column($portalDocs, 'name');
        $text = preg_replace(['~\h+~u', '~(\d)\s*k\s+V\b~'], [' ', '$1 kV'], $text);
        if (preg_match_all('~\b[A-Z]{2}\s*-\s*DCL\s*-\s*\d+~', $text) >= 2) return $this->coded($text, $portalDocs);
        // Section headers ("In case of Company:") start their own line; items under one carry it as their condition.
        $text = preg_replace([
            '~[ \t]+((?:In case of|In the case of|Common|Additional)\b[^:\n]{0,60}):[ \t]*~i',
            '~[ \t]+(In (?:the )?case of [A-Z][\w./& -]{1,45})[ \t]*$~m',
            '~(?<=[.)])[ \t]+(?=[A-Z][A-Za-z ]{2,24}:[ \t])~',
            '~(?<=\S)[ \t]*([\x{F0B7}\x{2022}\x{F0A7}])[ \t]*~u',
        ], ["\n$1:\n", "\n$1:", "\n", "\n$1 "], $text);

        $lines = array_values(array_filter(array_map('trim', preg_split("~\R~u", $text)), fn ($l) => $l !== '' && $l !== "\f"));
        $allMandatory = (bool) preg_match('~\(mandatory\)~i', $lines[0] ?? '');
        $items = []; $mode = 'list'; $nocLines = []; $tableRow = null; $any = null; $section = null;

        foreach ($lines as $i => $line) {
            if ($i === 0 && preg_match('~check\s*-?\s*list|documents? required~i', $line) && !preg_match('~^\d+[.)]\s~', $line)) continue;
            if (preg_match('~^#\s+Document Name\b.*Mandatory~i', $line)) { $mode = 'table'; continue; }
            if (preg_match('~^S\.?\s*No\.?\s+Department\b~i', $line)) { $mode = 'noc'; $this->flushRow($tableRow, $items, $knownNames); continue; }
            if ($mode === 'noc') { $nocLines[] = $line; continue; }
            if ($mode === 'table') {
                if (preg_match('~^(\d{1,3})\s+(.+)$~', $line, $m) && !preg_match('~^\d+\s*(kV|mm|m|sq)~i', $line)) { $this->flushRow($tableRow, $items, $knownNames); $tableRow = $m[2]; }
                elseif ($tableRow !== null) $tableRow .= ' ' . $line;
                continue;
            }
            if (preg_match('~^([^:]{3,80}):$~u', $line, $m) && !preg_match('~^(\d|[\x{F0B7}\x{2022}\x{F0A7}])~u', $line)) {
                $section = preg_match('~^(common|general)\b~i', $m[1]) ? null : ucfirst(trim($m[1]));
                $any = null;
                continue;
            }
            // "Service: Land Allotment", "Lease period: 90 Yrs." — facts about the service, not documents.
            if (preg_match('~^([A-Z][A-Za-z ]{2,24}):\s+(.+)$~', $line, $m) && !preg_match('~^(note|nb)\b~i', $m[1])) {
                $items[] = ['kind' => 'note', 'name' => $line, 'condition' => null, 'group_label' => null, 'is_required' => null];
                $section = null; $any = null;
                continue;
            }
            if (preg_match('~^\(?(\d{1,3})[.)]\s*(?=\S)(.+)$~', $line, $m) || preg_match('~^\(?([a-z]|[ivx]{1,4})[.)]\s+(.+)$~i', $line, $m)) {
                $items[] = $this->item($m[2], $allMandatory, null, $section);
                $any = preg_match('~\b(any (one )?of|one of|following|either)\b~i', $m[2]) ? array_key_last($items) : null;
                continue;
            }
            if (preg_match(self::BULLET, $line, $m)) {
                if (preg_match('~^([A-Z][A-Za-z ]{2,24}):\s+(.+)$~', $m[1])) {
                    $items[] = ['kind' => 'note', 'name' => $m[1], 'condition' => null, 'group_label' => null, 'is_required' => null];
                    $section = null; $any = null;
                    continue;
                }
                $item = $this->item($m[1], $allMandatory, null, $section);
                if ($any !== null) { $item['group_label'] = rtrim($items[$any]['name'], ': '); $item['is_required'] = false; $item['kind'] = 'option'; }
                $items[] = $item;
                continue;
            }
            if ($items && !preg_match('~^(note|nb)\b~i', $line)) {
                $last = array_key_last($items);
                $prev = $items[$last];
                $own = $prev['condition'] && $prev['condition'] !== $section ? ' (' . $prev['condition'] . ')' : '';
                $items[$last] = $this->item($prev['name'] . ($prev['is_required'] && !$allMandatory && $prev['kind'] === 'document' ? ' (M)' : '') . $own . ' ' . $line, $allMandatory, $prev, $section);
            } else {
                $items[] = ['kind' => 'note', 'name' => $line, 'condition' => null, 'group_label' => null, 'is_required' => null];
            }
        }
        $this->flushRow($tableRow, $items, $knownNames);
        if ($nocLines) $this->nocs(implode(' ', $nocLines), $items);

        foreach ($items as &$it) {
            if ($it['kind'] === 'document' && preg_match('~^(as per|the procedure|for provisions|procedure|rules)\b~i', $it['name'])) { $it['kind'] = 'note'; $it['is_required'] = null; }
            $it['name'] = trim(preg_replace('~\s+~u', ' ', str_replace('&AMP;', '&', $it['name'])), " .;:");
        }
        return array_values(array_filter($items, fn ($i) => $i['name'] !== ''));
    }

    private function item(string $text, bool $allMandatory, ?array $prev = null, ?string $section = null): array
    {
        $text = trim(preg_replace('~\s+~u', ' ', $text));
        $mandatory = false; $optional = false; $condition = null;
        if (preg_match('~\s*\((M|Mandatory)\)~i', $text)) { $mandatory = true; $text = trim(preg_replace('~\s*\((M|Mandatory)\)~i', '', $text)); }
        if (preg_match('~\s*\(\s*(Optional|Non[- ]?mandatory)\s*\)~i', $text)) { $optional = true; $text = trim(preg_replace('~\s*\(\s*(Optional|Non[- ]?mandatory)\s*\)~i', '', $text)); }
        // "(Where applicable)" / "(if any)" anywhere in the line is the item's condition.
        if (preg_match('~\s*\(\s*((?:where|if|as|wherever)\s+(?:applicable|any|required)[^)]*)\)\.?~i', $text, $m)) {
            $condition = ucfirst(trim($m[1], ' .'));
            $text = trim(str_replace($m[0], ' ', $text));
        }
        // A trailing ", if the venture is new" / "in case of existing unit" is the condition; the document name stays in front.
        if (!$condition && preg_match('~(?:,\s*|\s+)((?:if|only if|in case of|in the case of|where|wherever)\s+[a-z][^,()]{2,80})$~i', $text, $m)
            && str_contains(trim(substr($text, 0, -strlen($m[0]))), ' ') && !preg_match('~^(if|where)\s+(applicable|any|required)\b~i', $m[1])) {
            $condition = ucfirst(trim($m[1], ' .'));
            $text = trim(substr($text, 0, -strlen($m[0])), ' ,');
        }
        // The condition is split off only when a real name remains ("Documents if any court case…" stays whole).
        if (!$condition && preg_match(self::CONDITION, $text, $m) && str_contains(trim(substr($text, 0, -strlen($m[0]))), ' ')) {
            $condition = ucfirst(trim($m[1], ' ,.'));
            $text = trim(substr($text, 0, -strlen($m[0])), ' ,');
        }
        $condition ??= $section;
        $kind = $prev['kind'] ?? 'document';
        $required = $kind === 'option' || $optional ? false : ($mandatory || $allMandatory ? true : ($condition && $condition !== $section ? false : null));
        return ['kind' => $kind, 'name' => $text, 'condition' => $condition, 'group_label' => $prev['group_label'] ?? null, 'is_required' => $required];
    }

    /** Coded tables: "1 UK - DCL - 19 Photo Yes Self Photograph of Establishment Yes <check points>" (cells wrap word by word). */
    private function coded(string $text, array $portalDocs): array
    {
        $byCode = collect($portalDocs)->filter(fn ($d) => !empty($d['code']))->keyBy(fn ($d) => strtoupper(preg_replace('~\s+~', '', $d['code'])));
        $flat = $this->glue($text);
        $parts = preg_split('~(?:^|\s)(\d{1,3})\s+((?:[A-Z]{2}\s*-\s*DCL\s*-\s*\d+\s*/?\s*)+)~', $flat, -1, PREG_SPLIT_DELIM_CAPTURE);
        $items = [];
        for ($i = 1; $i + 2 < count($parts); $i += 3) {
            $codes = array_map(fn ($c) => strtoupper(preg_replace('~\s+~', '', $c)), preg_split('~\s*/\s*~', trim($parts[$i + 1], " /")));
            $rest = trim($parts[$i + 2]);
            // "<type> Yes|No <issued by> <name> Yes|No <check points>": the last Yes/No closes the name.
            $required = null; $checks = null; $body = $rest;
            if (preg_match_all('~\b(Yes|No)\b~', $rest, $yn, PREG_OFFSET_CAPTURE) && count($yn[0]) >= 1) {
                $last = end($yn[0]);
                $required = $last[0] === 'Yes';
                $checks = trim(substr($rest, $last[1] + strlen($last[0]))) ?: null;
                $body = trim(substr($rest, 0, $last[1]));
                if (count($yn[0]) >= 2) $body = trim(substr($body, $yn[0][0][1] + strlen($yn[0][0][0])));
            }
            $known = collect($codes)->map(fn ($c) => $byCode[$c]['name'] ?? null)->filter()->unique()->values();
            $items[] = ['kind' => 'document', 'code' => implode(' / ', $codes), 'name' => $known->isNotEmpty() ? $known->implode(' / ') : $body,
                'condition' => $checks ? mb_substr(ucfirst($checks), 0, 500) : null, 'group_label' => null, 'is_required' => $required];
        }
        return $items;
    }

    /** Joins wrapped table lines, gluing word fragments ("Establishme" + "nt") that a narrow cell split. */
    private function glue(string $text): string
    {
        $lines = array_values(array_filter(array_map('trim', preg_split("~\R~u", $text)), fn ($l) => $l !== '' && $l !== "\f"));
        $out = '';
        foreach ($lines as $line) {
            $first = strtok($line, ' ');
            $glue = $out !== '' && preg_match('~[a-z]$~', $out) && preg_match('~^[a-z]{1,3}[.,)]?$~', $first) && !in_array(rtrim($first, '.,)'), self::SHORT_WORDS, true);
            $out .= ($glue ? '' : ' ') . $line;
        }
        return trim(preg_replace(['~\s+~u', '~\s*-\s*(?=[a-z])~'], [' ', '-'], $out));
    }

    private function flushRow(?string &$row, array &$items, array $known): void
    {
        if ($row === null) return;
        $row = trim($row); $required = null;
        if (preg_match('~\s(Y|N|Yes|No)$~i', $row, $m)) { $required = strtoupper($m[1][0]) === 'Y'; $row = trim(substr($row, 0, -strlen($m[0]))); }
        $name = $row; $condition = null;
        $norm = fn ($s) => strtolower(preg_replace('~[^a-z0-9]~i', '', $s));
        foreach (collect($known)->sortByDesc(fn ($k) => strlen($k)) as $k) {
            $nk = $norm($k);
            if ($nk === '' || !str_starts_with($norm($row), $nk)) continue;
            // Walk the row until the normalised prefix length matches; the rest is the condition.
            $len = 0; $cut = 0;
            for ($i = 0, $n = strlen($row); $i < $n && $len < strlen($nk); $i++) { if (ctype_alnum($row[$i])) $len++; $cut = $i + 1; }
            $name = trim(substr($row, 0, $cut)); $condition = trim(substr($row, $cut));
            break;
        }
        if ($condition === null && preg_match('~^(.+?)\s+((?:only|if|where|in case|for)\b.+|-)$~i', $row, $m)) { $name = $m[1]; $condition = $m[2]; }
        $condition = $condition === null || trim($condition, ' -') === '' ? null : ucfirst(trim($condition, ' -'));
        $items[] = ['kind' => 'document', 'name' => $name, 'condition' => $condition, 'group_label' => null, 'is_required' => $required];
        $row = null;
    }

    private function nocs(string $grid, array &$items): void
    {
        preg_match_all('~(?:^|\s)(\d{1,2})\s+(.+?)(?=\s+\d{1,2}\s+\D|$)~u', trim($grid), $m, PREG_SET_ORDER);
        $rows = collect($m)->mapWithKeys(fn ($r) => [(int) $r[1] => trim($r[2])])->sortKeys();
        $parent = collect($items)->last(fn ($i) => preg_match('~\bNOC~i', $i['name']));
        $label = $parent ? $parent['name'] : 'NOCs from departments';
        foreach ($rows as $dept) {
            $items[] = ['kind' => 'noc', 'name' => $dept, 'condition' => $parent['condition'] ?? 'As applicable', 'group_label' => $label, 'is_required' => false];
        }
    }
}
