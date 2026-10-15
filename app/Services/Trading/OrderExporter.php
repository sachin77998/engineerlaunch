<?php

namespace App\Services\Trading;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use RuntimeException;
use ZipArchive;

/** Order exports: Excel (.xlsx written directly) and PDF (dompdf). */
class OrderExporter
{
    public const COLUMNS = [
        'order_no' => 'Order no', 'created_at' => 'Placed at', 'client_code' => 'Client', 'scheme_code' => 'Scheme code', 'scheme' => 'Scheme',
        'side' => 'Side', 'status' => 'Status', 'amount' => 'Amount (₹)', 'units' => 'Units', 'nav' => 'NAV', 'applicable_nav_date' => 'NAV date',
        'allotted_units' => 'Allotted units', 'stamp_duty' => 'Stamp duty', 'transaction_charge' => 'Txn charge', 'platform_fee' => 'Platform fee',
        'gst' => 'GST', 'total_fees' => 'Total fees', 'net_amount' => 'Net amount', 'channel' => 'Channel', 'external_ref' => 'Reference',
    ];

    /** Flat rows for export. */
    public function rows(Collection $orders): array
    {
        return $orders->map(fn ($o) => [
            'order_no' => $o->order_no, 'created_at' => $o->created_at?->timezone('Asia/Kolkata')->format('d-m-Y H:i'), 'client_code' => $o->client?->client_code,
            'scheme_code' => $o->scheme_code, 'scheme' => $o->scheme ? $o->scheme->name . ' · ' . $o->scheme->plan . ' · ' . $o->scheme->option : '', 'side' => strtoupper($o->side),
            'status' => $o->status, 'amount' => $o->amount, 'units' => $o->units ?? $o->allotted_units, 'nav' => $o->nav, 'applicable_nav_date' => $o->applicable_nav_date?->format('d-m-Y'),
            'allotted_units' => $o->allotted_units, 'stamp_duty' => $o->stamp_duty, 'transaction_charge' => $o->transaction_charge, 'platform_fee' => $o->platform_fee,
            'gst' => $o->gst, 'total_fees' => $o->total_fees, 'net_amount' => $o->net_amount, 'channel' => $o->channel, 'external_ref' => $o->external_ref,
        ])->all();
    }

    /** Writes an .xlsx file and returns its path. */
    public function xlsx(array $rows, string $title = 'Orders', ?array $columns = null): string
    {
        if (!class_exists(ZipArchive::class)) throw new RuntimeException('The zip extension is required for Excel export.');
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();
        $zip->open($path, ZipArchive::OVERWRITE);
        $esc = fn ($v) => htmlspecialchars((string) $v, ENT_XML1 | ENT_QUOTES, 'UTF-8');
        $col = function (int $i) { $s = ''; for ($i++; $i > 0; $i = intdiv($i - 1, 26)) $s = chr(65 + ($i - 1) % 26) . $s; return $s; };
        $sheet = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews><sheetData>';
        $columns ??= self::COLUMNS;
        $all = array_merge([array_values($columns)], array_map('array_values', $rows));
        foreach ($all as $r => $row) {
            $sheet .= '<row r="' . ($r + 1) . '">';
            foreach (array_values($row) as $c => $value) {
                $ref = $col($c) . ($r + 1);
                $style = $r === 0 ? ' s="1"' : '';
                $sheet .= is_int($value) || is_float($value)
                    ? '<c r="' . $ref . '"' . $style . '><v>' . $value . '</v></c>'
                    : '<c r="' . $ref . '" t="inlineStr"' . $style . '><is><t>' . $esc($value) . '</t></is></c>';
            }
            $sheet .= '</row>';
        }
        $sheet .= '</sheetData><autoFilter ref="A1:' . $col(count($columns) - 1) . max(1, count($all)) . '"/></worksheet>';
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/><Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="' . $esc(mb_substr($title, 0, 31)) . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/><Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
        $zip->addFromString('xl/styles.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts><fills count="2"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill></fills><borders count="1"><border/></borders><cellStyleXfs count="1"><xf/></cellStyleXfs><cellXfs count="2"><xf/><xf fontId="1" applyFont="1"/></cellXfs></styleSheet>');
        $zip->addFromString('xl/worksheets/sheet1.xml', $sheet);
        $zip->close();
        return $path;
    }

    public function pdf(array $rows, string $title, array $summary = [])
    {
        return Pdf::loadView('trading.exports.orders-pdf', ['rows' => $rows, 'title' => $title, 'summary' => $summary, 'columns' => self::COLUMNS])->setPaper('a4', 'landscape');
    }
}
