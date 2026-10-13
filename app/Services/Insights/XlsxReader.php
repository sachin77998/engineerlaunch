<?php

namespace App\Services\Insights;

use RuntimeException;
use XMLReader;
use ZipArchive;

/**
 * Minimal .xlsx reader (no extra packages): yields every row of every worksheet as a list of cell strings.
 * Used for MCA's Excel downloads, e.g. "Companies registered during the month". Rows are streamed, so files with
 * lakhs of rows do not need to fit in memory.
 */
class XlsxReader
{
    /** @return \Generator<int, array<int, string>> */
    public function rows(string $path): \Generator
    {
        $zip = new ZipArchive();
        if ($zip->open($path) !== true) throw new RuntimeException('The Excel file could not be opened.');
        $shared = $this->sharedStrings($zip);
        $sheets = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (preg_match('~^xl/worksheets/sheet(\d+)\.xml$~', $name, $m)) $sheets[(int) $m[1]] = $name;
        }
        ksort($sheets);
        $zip->close();

        foreach ($sheets as $sheet) {
            $reader = new XMLReader();
            if (!$reader->open('zip://' . $path . '#' . $sheet, null, LIBXML_NONET | LIBXML_COMPACT)) continue;
            $row = null;
            $col = 0;
            $type = null;
            while ($reader->read()) {
                if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'row') {
                    $row = [];
                    if ($reader->isEmptyElement) { yield $row; $row = null; }
                } elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'c' && $row !== null) {
                    $col = $this->column((string) $reader->getAttribute('r'), count($row));
                    $type = $reader->getAttribute('t');
                    if ($reader->isEmptyElement) $row[$col] = '';
                } elseif ($reader->nodeType === XMLReader::ELEMENT && in_array($reader->name, ['v', 't'], true) && $row !== null) {
                    $value = $reader->readString();
                    if ($type === 's') $value = $shared[(int) $value] ?? '';
                    $row[$col] = ($row[$col] ?? '') . $value;
                } elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'row' && $row !== null) {
                    if ($row) {
                        $filled = array_fill(0, max(array_keys($row)) + 1, '');
                        foreach ($row as $i => $v) $filled[$i] = $v;
                        yield $filled;
                    }
                    $row = null;
                }
            }
            $reader->close();
        }
    }

    private function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');
        if ($xml === false) return [];
        $strings = [];
        $reader = new XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);
        $current = null;
        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 'si') $current = '';
            elseif ($reader->nodeType === XMLReader::ELEMENT && $reader->name === 't' && $current !== null) $current .= $reader->readString();
            elseif ($reader->nodeType === XMLReader::END_ELEMENT && $reader->name === 'si') { $strings[] = $current; $current = null; }
        }
        return $strings;
    }

    /** Zero-based column index from a cell reference such as "AB12". */
    private function column(string $reference, int $fallback): int
    {
        if (!preg_match('/^([A-Z]+)/', $reference, $m)) return $fallback;
        $index = 0;
        foreach (str_split($m[1]) as $letter) $index = $index * 26 + (ord($letter) - 64);
        return $index - 1;
    }
}
