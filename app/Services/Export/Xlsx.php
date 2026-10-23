<?php

namespace App\Services\Export;

use RuntimeException;
use ZipArchive;

// Minimal multi-sheet .xlsx writer: bold, frozen header row, auto-filter, numbers stored as numbers.
class Xlsx
{
    /** @param array<string, array{0: array, 1: array}> $sheets name => [header labels, rows]; returns the temp file path. */
    public function write(array $sheets): string
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('The zip PHP extension is required for Excel export.');
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i) { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s; };
        $names = []; $i = 0;
        foreach ($sheets as $name => [$header, $rows]) {
            $i++;
            $name = mb_substr(preg_replace('/[\\\\\/?*\[\]:]/', ' ', $name), 0, 31) ?: 'Sheet' . $i;
            $names[$i] = $name;
            $all = array_merge([array_values($header)], array_map('array_values', $rows));
            $xml = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
                . '<cols>' . implode('', array_map(fn ($c) => '<col min="' . ($c + 1) . '" max="' . ($c + 1) . '" width="' . ($c === 0 ? 34 : 18) . '" customWidth="1"/>', array_keys(array_values($header) ?: [0]))) . '</cols><sheetData>';
            foreach ($all as $r => $row) {
                $xml .= '<row r="' . ($r + 1) . '">';
                foreach ($row as $c => $v) {
                    $ref = $col($c) . ($r + 1);
                    $style = $r === 0 ? ' s="1"' : '';
                    if ($v === null || $v === '') continue;
                    $xml .= (is_int($v) || is_float($v)) ? '<c r="' . $ref . '"' . $style . '><v>' . $v . '</v></c>'
                        : '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t xml:space="preserve">' . $esc($v) . '</t></is></c>';
                }
                $xml .= '</row>';
            }
            $xml .= '</sheetData>' . (count($header) ? '<autoFilter ref="A1:' . $col(count($header) - 1) . max(1, count($all)) . '"/>' : '') . '</worksheet>';
            $zip->addFromString("xl/worksheets/sheet$i.xml", $xml);
        }
        $overrides = implode('', array_map(fn ($n) => '<Override PartName="/xl/worksheets/sheet' . $n . '.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>', array_keys($names)));
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>' . $overrides . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets>'
            . implode('', array_map(fn ($n, $name) => '<sheet name="' . $esc($name) . '" sheetId="' . $n . '" r:id="rId' . $n . '"/>', array_keys($names), $names)) . '</sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . implode('', array_map(fn ($n) => '<Relationship Id="rId' . $n . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet' . $n . '.xml"/>', array_keys($names)))
            . '<Relationship Id="rId' . (count($names) + 1) . '" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts><fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF0B2545"/></patternFill></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/></cellXfs></styleSheet>');
        $zip->close();
        return $path;
    }
}
