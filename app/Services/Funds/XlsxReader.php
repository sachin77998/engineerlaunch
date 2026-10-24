<?php

namespace App\Services\Funds;

use RuntimeException;
use ZipArchive;

/**
 * Minimal .xlsx reader (zip + XML, no extra packages): returns every sheet as rows of cell text.
 * Handles shared strings, inline strings, numbers and booleans; gaps between cells are kept as empty strings.
 */
class XlsxReader
{
    /** @return array<string, array<int, array<int, string>>> sheet name => rows */
    public function read(string $path, int $maxRows = 5000): array
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('Not an xlsx file');
        try {
            $strings = $this->sharedStrings($zip);
            $sheets = [];
            foreach ($this->sheetPaths($zip) as $name => $file) {
                $xml = $zip->getFromName($file);
                if ($xml === false) continue;
                $sheets[$name] = $this->rows($xml, $strings, $maxRows);
            }
            return $sheets;
        } finally {
            $zip->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $out = [];
        $r = new \XMLReader();
        $r->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $buf = null;
        while ($r->read()) {
            if ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 'si') $buf = '';
            elseif ($r->nodeType === \XMLReader::ELEMENT && $r->localName === 't' && $buf !== null) $buf .= $r->readString();
            elseif ($r->nodeType === \XMLReader::END_ELEMENT && $r->localName === 'si') { $out[] = $buf; $buf = null; }
        }
        return $out;
    }

    /** Sheet names in workbook order mapped to their XML part. */
    private function sheetPaths(ZipArchive $zip): array
    {
        $wb = $zip->getFromName('xl/workbook.xml');
        $rels = $zip->getFromName('xl/_rels/workbook.xml.rels');
        if ($wb === false) return [];
        $target = [];
        if ($rels !== false && preg_match_all('/<Relationship\b[^>]*>/', $rels, $m)) {
            foreach ($m[0] as $tag) {
                preg_match('/\bId="([^"]+)"/', $tag, $id); preg_match('/\bTarget="([^"]+)"/', $tag, $t);
                if ($id && $t) $target[$id[1]] = str_starts_with($t[1], '/') ? ltrim($t[1], '/') : 'xl/' . ltrim($t[1], './');
            }
        }
        $out = [];
        preg_match_all('/<sheet\b[^>]*>/', $wb, $m);
        foreach ($m[0] as $i => $tag) {
            preg_match('/\bname="([^"]*)"/', $tag, $n); preg_match('/\br:id="([^"]+)"/', $tag, $rid);
            $name = html_entity_decode($n[1] ?? 'Sheet' . ($i + 1), ENT_QUOTES | ENT_XML1);
            $out[$name] = $target[$rid[1] ?? ''] ?? 'xl/worksheets/sheet' . ($i + 1) . '.xml';
        }
        return $out;
    }

    private function rows(string $xml, array $strings, int $maxRows): array
    {
        $rows = [];
        $r = new \XMLReader();
        $r->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $row = null; $col = 0; $type = null; $value = null;
        while ($r->read()) {
            if ($r->nodeType === \XMLReader::ELEMENT) {
                switch ($r->localName) {
                    case 'row': $row = []; $col = 0; if ($r->isEmptyElement) { $rows[] = []; $row = null; } break;
                    case 'c':
                        $ref = $r->getAttribute('r');
                        $col = $ref ? $this->colIndex($ref) : $col + 1;
                        $type = $r->getAttribute('t'); $value = '';
                        if ($r->isEmptyElement) { $row[$col] = ''; }
                        break;
                    case 'v': $value = $r->readString(); break;
                    case 't': if ($type === 'inlineStr') $value .= $r->readString(); break;
                }
            } elseif ($r->nodeType === \XMLReader::END_ELEMENT) {
                if ($r->localName === 'c' && $row !== null) {
                    $row[$col] = match ($type) { 's' => $strings[(int) $value] ?? '', 'b' => $value === '1' ? 'TRUE' : 'FALSE', default => (string) $value };
                } elseif ($r->localName === 'row' && $row !== null) {
                    $max = $row ? max(array_keys($row)) : -1;
                    $dense = [];
                    for ($i = 0; $i <= $max; $i++) $dense[] = trim((string) ($row[$i] ?? ''));
                    $rows[] = $dense;
                    $row = null;
                    if (count($rows) >= $maxRows) break;
                }
            }
        }
        return $rows;
    }

    private function colIndex(string $ref): int
    {
        preg_match('/^[A-Z]+/', strtoupper($ref), $m);
        $n = 0;
        foreach (str_split($m[0] ?? 'A') as $ch) $n = $n * 26 + (ord($ch) - 64);
        return $n - 1;
    }
}
