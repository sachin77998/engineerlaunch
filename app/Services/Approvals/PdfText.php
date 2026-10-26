<?php

namespace App\Services\Approvals;

/**
 * Plain-text extraction from simple text PDFs (Word / Excel exports): Flate streams, object streams, WinAnsi fonts and
 * Identity-H fonts with ToUnicode maps. Returns one string per page with a line break wherever the text moves down.
 */
class PdfText
{
    private array $objects = [];
    private array $cmaps = [];

    /** @return string[] page texts */
    public function pages(string $pdf): array
    {
        $this->objects = []; $this->cmaps = [];
        $this->readObjects($pdf);
        $pages = [];
        foreach ($this->objects as $body) {
            if (!preg_match('~/Type\s*/Page(?![s\w])~', $body)) continue;
            $fonts = $this->fonts($body);
            $text = '';
            foreach ($this->contents($body) as $stream) $text .= $this->runText($stream, $fonts) . "\n";
            $pages[] = trim(preg_replace("~[ \t]+~", ' ', preg_replace("~\n\s*\n+~", "\n", $text)));
        }
        return $pages;
    }

    private function readObjects(string $pdf): void
    {
        preg_match_all('~(\d+)\s+0\s+obj\b(.*?)\bendobj~s', $pdf, $m, PREG_SET_ORDER);
        foreach ($m as [, $id, $body]) $this->objects[(int) $id] = $body;
        foreach ($this->objects as $body) {
            if (!preg_match('~/Type\s*/ObjStm~', $body)) continue;
            $data = $this->stream($body);
            if ($data === null || !preg_match('~/N\s+(\d+)~', $body, $n) || !preg_match('~/First\s+(\d+)~', $body, $f)) continue;
            $head = preg_split('~\s+~', trim(substr($data, 0, (int) $f[1])));
            $pairs = array_chunk($head, 2);
            foreach ($pairs as $i => [$oid, $off]) {
                $start = (int) $f[1] + (int) $off;
                $end = isset($pairs[$i + 1]) ? (int) $f[1] + (int) $pairs[$i + 1][1] : strlen($data);
                $this->objects[(int) $oid] ??= substr($data, $start, $end - $start);
            }
        }
    }

    private function stream(string $body): ?string
    {
        if (!preg_match('~stream\r?\n(.*)endstream~s', $body, $m)) return null;
        $raw = preg_replace('~\r?\n$~', '', $m[1]);
        if (!str_contains($body, 'FlateDecode')) return $raw;
        $out = @gzuncompress($raw);
        if ($out === false) $out = @gzinflate(substr($raw, 2));
        return $out === false ? null : $out;
    }

    private function deref(string $value): string
    {
        return preg_match('~^\s*(\d+)\s+0\s+R~', $value, $m) ? ($this->objects[(int) $m[1]] ?? '') : $value;
    }

    /** Dictionary value for a key, following an indirect reference; nested << >> kept whole. */
    private function entry(string $dict, string $key): ?string
    {
        if (!preg_match('~/' . $key . '\s*~', $dict, $m, PREG_OFFSET_CAPTURE)) return null;
        $pos = $m[0][1] + strlen($m[0][0]);
        if (substr($dict, $pos, 2) === '<<') {
            $depth = 0;
            for ($i = $pos, $len = strlen($dict); $i < $len - 1; $i++) {
                if (substr($dict, $i, 2) === '<<') { $depth++; $i++; }
                elseif (substr($dict, $i, 2) === '>>') { $depth--; $i++; if ($depth === 0) return substr($dict, $pos, $i - $pos + 1); }
            }
            return null;
        }
        if (preg_match('~\G(\d+\s+0\s+R)~', $dict, $r, 0, $pos)) return $this->deref($r[1]);
        if (preg_match('~\G(\[[^\]]*\])~', $dict, $r, 0, $pos)) return $r[1];
        return null;
    }

    /** Font resource name => [bytes per code, code => text]. Inherits Resources from parent Pages nodes. */
    private function fonts(string $page): array
    {
        $res = $this->entry($page, 'Resources');
        $node = $page;
        while ($res === null && ($parent = $this->entry($node, 'Parent'))) { $res = $this->entry($parent, 'Resources'); $node = $parent; }
        $fontDict = $res ? $this->entry($res, 'Font') : null;
        $out = [];
        if (!$fontDict) return $out;
        preg_match_all('~/([\w.+-]+)\s+(\d+)\s+0\s+R~', $fontDict, $m, PREG_SET_ORDER);
        foreach ($m as [, $name, $id]) {
            $font = $this->objects[(int) $id] ?? '';
            $wide = str_contains($font, 'Identity-H') || str_contains($font, 'Type0');
            $map = [];
            if (preg_match('~/ToUnicode\s+(\d+)\s+0\s+R~', $font, $t)) $map = $this->cmaps[(int) $t[1]] ??= $this->cmap($this->stream($this->objects[(int) $t[1]] ?? '') ?? '');
            $out[$name] = [$wide ? 2 : 1, $map];
        }
        return $out;
    }

    private function cmap(string $data): array
    {
        $map = [];
        $u = fn ($hex) => mb_convert_encoding(hex2bin(strlen($hex) % 2 ? '0' . $hex : $hex), 'UTF-8', 'UTF-16BE');
        foreach (preg_match_all('~beginbfchar(.*?)endbfchar~s', $data, $b) ? $b[1] : [] as $block) {
            preg_match_all('~<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>~', $block, $p, PREG_SET_ORDER);
            foreach ($p as [, $src, $dst]) $map[hexdec($src)] = $u($dst);
        }
        foreach (preg_match_all('~beginbfrange(.*?)endbfrange~s', $data, $b) ? $b[1] : [] as $block) {
            preg_match_all('~<([0-9A-Fa-f]+)>\s*<([0-9A-Fa-f]+)>\s*(<[0-9A-Fa-f]+>|\[[^\]]*\])~', $block, $p, PREG_SET_ORDER);
            foreach ($p as [, $lo, $hi, $dst]) {
                $lo = hexdec($lo); $hi = hexdec($hi);
                if ($dst[0] === '[') {
                    preg_match_all('~<([0-9A-Fa-f]+)>~', $dst, $list);
                    foreach ($list[1] as $i => $hex) $map[$lo + $i] = $u($hex);
                } else {
                    $base = hexdec(trim($dst, '<>'));
                    for ($c = $lo; $c <= $hi && $c - $lo < 512; $c++) $map[$c] = mb_chr($base + $c - $lo, 'UTF-8');
                }
            }
        }
        return $map;
    }

    private function contents(string $page): array
    {
        if (!preg_match('~/Contents\s*(\[[^\]]*\]|\d+\s+0\s+R)~', $page, $m)) return [];
        preg_match_all('~(\d+)\s+0\s+R~', $m[1], $refs);
        $out = [];
        foreach ($refs[1] as $id) {
            $obj = $this->objects[(int) $id] ?? '';
            if (str_starts_with(ltrim($obj), '[')) { foreach ($this->contents('/Contents ' . trim($obj)) as $s) $out[] = $s; continue; }
            if (($s = $this->stream($obj)) !== null) $out[] = $s;
        }
        return $out;
    }

    private function runText(string $ops, array $fonts): string
    {
        $text = ''; $font = [1, []]; $y = null;
        preg_match_all('~/([\w.+-]+)\s+[\d.]+\s+Tf|([-\d.]+)\s+([-\d.]+)\s+T[dD]|[-\d.]+\s+[-\d.]+\s+[-\d.]+\s+[-\d.]+\s+([-\d.]+)\s+([-\d.]+)\s+Tm|(T\*)|(\((?:\\\\.|[^\\\\)])*\)|<[0-9A-Fa-f\s]*>)\s*(?:Tj|\'|")|\[((?:\\\\.|[^\]])*)\]\s*TJ~s', $ops, $m, PREG_SET_ORDER);
        foreach ($m as $op) {
            if (($op[1] ?? '') !== '') { $font = $fonts[$op[1]] ?? [1, []]; continue; }
            if (($op[3] ?? '') !== '') { if ((float) $op[3] < -0.5) $text .= "\n"; elseif ((float) $op[2] > 1) $text .= ' '; continue; }
            if (($op[5] ?? '') !== '') { $ny = (float) $op[5]; if ($y !== null && abs($ny - $y) > 1) $text .= "\n"; elseif ($y !== null) $text .= ' '; $y = $ny; continue; }
            if (($op[6] ?? '') !== '') { $text .= "\n"; continue; }
            if (($op[7] ?? '') !== '') { $text .= $this->decode($op[7], $font); continue; }
            if (($op[8] ?? '') !== '') {
                preg_match_all('~\((?:\\\\.|[^\\\\)])*\)|<[0-9A-Fa-f\s]*>|(-?[\d.]+)~s', $op[8], $parts, PREG_SET_ORDER);
                foreach ($parts as $p) $text .= isset($p[1]) && $p[1] !== '' ? ((float) $p[1] < -180 ? ' ' : '') : $this->decode($p[0], $font);
            }
        }
        return $text;
    }

    private function decode(string $token, array $font): string
    {
        [$width, $map] = $font;
        if ($token[0] === '<') $bytes = hex2bin(preg_replace('~\s+~', '', strlen(trim($token, '<> ')) % 2 ? trim($token, '<>') . '0' : trim($token, '<>')));
        else $bytes = preg_replace_callback('~\\\\([nrtbf()\\\\]|[0-7]{1,3}|\r?\n)~', fn ($e) => match ($e[1]) {
            'n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\f", '(' => '(', ')' => ')', '\\' => '\\',
            default => ctype_digit($e[1]) ? chr(octdec($e[1]) & 255) : '',
        }, substr($token, 1, -1));
        $out = '';
        for ($i = 0, $len = strlen($bytes); $i + $width <= $len; $i += $width) {
            $code = $width === 2 ? (ord($bytes[$i]) << 8) | ord($bytes[$i + 1]) : ord($bytes[$i]);
            $out .= $map[$code] ?? ($width === 1 ? mb_convert_encoding(chr($code), 'UTF-8', 'Windows-1252') : '');
        }
        return $out;
    }
}
