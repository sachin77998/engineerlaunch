<?php
namespace App\Services\Insights;

/**
 * Plain-text extraction for text-based PDFs (rating-agency rationales), without external binaries or packages.
 * Handles compressed streams, object streams, Type0/Identity-H fonts with ToUnicode maps and WinAnsi simple fonts.
 * Text runs are placed by their page position, so table rows come out as one line each. Pages are separated by "\f".
 */
class PdfText
{
    private string $pdf = '';
    private array $offsets = [];
    private array $objects = [];
    private array $fonts = [];

    public function extract(string $pdf): string
    {
        if (!str_starts_with(ltrim($pdf), '%PDF')) return '';
        $this->pdf = $pdf;
        $this->offsets = $this->objects = $this->fonts = [];
        preg_match_all('/(?<![0-9])(\d+)\s+(\d+)\s+obj\b/', $pdf, $m, PREG_OFFSET_CAPTURE);
        foreach ($m[1] as $i => [$num]) $this->offsets[(int) $num] = $m[0][$i][1] + strlen($m[0][$i][0]);
        $this->loadObjectStreams();
        $root = preg_match_all('/\/Root\s+(\d+)\s+\d+\s+R/', $pdf, $r) ? (int) end($r[1]) : null;
        $catalog = $root ? $this->resolve([ 'ref', $root]) : null;
        $pages = [];
        if (is_array($catalog) && isset($catalog['Pages'])) $this->collectPages($this->resolve($catalog['Pages']), [], $pages, 0);
        $out = [];
        foreach ($pages as [$page, $resources]) $out[] = $this->pageText($page, $resources);
        return implode("\f", $out);
    }

    // ---- objects ----

    private function object(int $num)
    {
        if (array_key_exists($num, $this->objects)) return $this->objects[$num];
        if (!isset($this->offsets[$num])) return $this->objects[$num] = null;
        $this->objects[$num] = null; // guards against reference cycles
        $pos = $this->offsets[$num];
        $value = $this->parse($this->pdf, $pos);
        if (is_array($value) && preg_match('/\Gstream\r?\n/', $this->pdf, $s, 0, $this->skipSpace($this->pdf, $pos))) {
            $start = $this->skipSpace($this->pdf, $pos) + strlen($s[0]);
            $length = $this->resolve($value['Length'] ?? null);
            if (!is_int($length) || substr($this->pdf, $start + $length, 20) === false || !preg_match('/^\s*endstream/', substr($this->pdf, $start + $length, 20))) {
                $end = strpos($this->pdf, 'endstream', $start);
                $length = $end === false ? 0 : $end - $start;
            }
            $value['__stream'] = substr($this->pdf, $start, $length);
        }
        return $this->objects[$num] = $value;
    }

    private function loadObjectStreams(): void
    {
        foreach (array_keys($this->offsets) as $num) {
            $head = substr($this->pdf, $this->offsets[$num], 400);
            if (!preg_match('/\/Type\s*\/ObjStm/', $head)) continue;
            $obj = $this->object($num);
            $data = is_array($obj) ? $this->streamData($obj) : '';
            $count = (int) ($obj['N'] ?? 0); $first = (int) ($obj['First'] ?? 0);
            if ($data === '' || !$count) continue;
            preg_match_all('/\d+/', substr($data, 0, $first), $nums);
            for ($i = 0; $i + 1 < count($nums[0]) && $i < $count * 2; $i += 2) {
                $inner = (int) $nums[0][$i];
                if (isset($this->offsets[$inner]) || array_key_exists($inner, $this->objects)) continue;
                $p = $first + (int) $nums[0][$i + 1];
                $this->objects[$inner] = $this->parse($data, $p);
            }
        }
    }

    private function resolve($value, int $depth = 0)
    {
        while (is_array($value) && ($value[0] ?? null) === 'ref' && count($value) === 2 && $depth++ < 20) $value = $this->object($value[1]);
        return $value;
    }

    private function streamData(array $obj): string
    {
        $data = $obj['__stream'] ?? '';
        $filters = $this->resolve($obj['Filter'] ?? null);
        $filters = $filters === null ? [] : (is_array($filters) && !isset($filters['__name']) ? $filters : [$filters]);
        foreach ($filters as $filter) {
            $name = is_array($filter) ? ($filter['__name'] ?? '') : '';
            if ($name === 'FlateDecode') {
                $decoded = @gzuncompress($data);
                if ($decoded === false) $decoded = @gzinflate(substr($data, 2));
                if ($decoded === false) $decoded = @zlib_decode($data);
                if ($decoded === false) return '';
                $data = $decoded;
            } elseif ($name !== '') {
                return ''; // images and other encodings carry no text
            }
        }
        return $data;
    }

    // ---- value parser: dicts as assoc arrays, arrays as lists, names as ['__name'=>..], refs as ['ref', n] ----

    private function skipSpace(string $s, int $p): int
    {
        $n = strlen($s);
        while ($p < $n) {
            $c = $s[$p];
            if ($c === '%') { while ($p < $n && $s[$p] !== "\n" && $s[$p] !== "\r") $p++; continue; }
            if (strpos(" \t\r\n\f\0", $c) === false) break;
            $p++;
        }
        return $p;
    }

    private function parse(string $s, int &$p)
    {
        $p = $this->skipSpace($s, $p);
        $c = $s[$p] ?? '';
        if ($c === '<' && ($s[$p + 1] ?? '') === '<') {
            $p += 2; $dict = [];
            while (true) {
                $p = $this->skipSpace($s, $p);
                if (($s[$p] ?? '>') === '>') { $p += 2; break; }
                $key = $this->parse($s, $p);
                if (!is_array($key) || !isset($key['__name'])) { $p++; continue; }
                $dict[$key['__name']] = $this->parse($s, $p);
            }
            return $dict;
        }
        if ($c === '[') {
            $p++; $list = [];
            while (true) {
                $p = $this->skipSpace($s, $p);
                if (!isset($s[$p]) || $s[$p] === ']') { $p++; break; }
                $list[] = $this->parse($s, $p);
            }
            return $list;
        }
        if ($c === '/') {
            preg_match('/\G\/([^\s\/\[\]()<>{}%]*)/', $s, $m, 0, $p); $p += strlen($m[0]);
            return ['__name' => preg_replace_callback('/#([0-9a-fA-F]{2})/', fn ($h) => chr(hexdec($h[1])), $m[1])];
        }
        if ($c === '(') return ['__str' => $this->literal($s, $p)];
        if ($c === '<') {
            $end = strpos($s, '>', $p); $hex = preg_replace('/\s+/', '', substr($s, $p + 1, $end - $p - 1)); $p = $end + 1;
            if (strlen($hex) % 2) $hex .= '0';
            return ['__str' => (string) @hex2bin($hex)];
        }
        if (preg_match('/\G(\d+)\s+(\d+)\s+R\b/', $s, $m, 0, $p)) { $p += strlen($m[0]); return ['ref', (int) $m[1]]; }
        if (preg_match('/\G[+-]?(\d+\.?\d*|\.\d+)/', $s, $m, 0, $p)) { $p += strlen($m[0]); return str_contains($m[0], '.') ? (float) $m[0] : (int) $m[0]; }
        if (preg_match('/\G(true|false|null)/', $s, $m, 0, $p)) { $p += strlen($m[0]); return $m[0] === 'null' ? null : $m[0] === 'true'; }
        $p++;
        return null;
    }

    private function literal(string $s, int &$p): string
    {
        $p++; $depth = 1; $out = ''; $n = strlen($s);
        while ($p < $n) {
            $c = $s[$p++];
            if ($c === '\\') {
                $e = $s[$p++] ?? '';
                $map = ['n' => "\n", 'r' => "\r", 't' => "\t", 'b' => "\x08", 'f' => "\f", '(' => '(', ')' => ')', '\\' => '\\'];
                if (isset($map[$e])) $out .= $map[$e];
                elseif (ctype_digit($e)) { $oct = $e; while (strlen($oct) < 3 && ctype_digit($s[$p] ?? 'x')) $oct .= $s[$p++]; $out .= chr(octdec($oct) & 255); }
                elseif ($e === "\r") { if (($s[$p] ?? '') === "\n") $p++; }
                elseif ($e !== "\n") $out .= $e;
                continue;
            }
            if ($c === '(') $depth++;
            if ($c === ')' && --$depth === 0) break;
            $out .= $c;
        }
        return $out;
    }

    // ---- pages ----

    private function collectPages($node, $inherited, array &$pages, int $depth): void
    {
        if (!is_array($node) || $depth > 30) return;
        $resources = isset($node['Resources']) ? $this->resolve($node['Resources']) : $inherited;
        if (($node['Type']['__name'] ?? '') === 'Pages' || isset($node['Kids'])) {
            foreach ((array) $this->resolve($node['Kids'] ?? []) as $kid) $this->collectPages($this->resolve($kid), $resources, $pages, $depth + 1);
            return;
        }
        $pages[] = [$node, $resources];
    }

    private function pageText(array $page, $resources): string
    {
        $contents = $this->resolve($page['Contents'] ?? null);
        $streams = is_array($contents) && isset($contents['__stream']) ? [$contents] : array_map(fn ($c) => $this->resolve($c), is_array($contents) ? $contents : []);
        $data = implode("\n", array_map(fn ($c) => is_array($c) ? $this->streamData($c) : '', $streams));
        $fontDict = is_array($resources) ? (array) $this->resolve($resources['Font'] ?? []) : [];
        return $this->layout($this->runs($data, $fontDict, is_array($resources) ? $resources : [], 0));
    }

    private function font(string $name, array $fontDict): array
    {
        $ref = $fontDict[$name] ?? null;
        $key = is_array($ref) && ($ref[0] ?? null) === 'ref' ? 'r' . $ref[1] : 'n' . $name . spl_object_id((object) $fontDict);
        if (isset($this->fonts[$key])) return $this->fonts[$key];
        $font = $this->resolve($ref);
        $info = ['bytes' => 1, 'map' => [], 'winansi' => true, 'widths' => [], 'dw' => 500];
        if (is_array($font)) {
            $info['bytes'] = ($font['Subtype']['__name'] ?? '') === 'Type0' ? 2 : 1;
            if ($info['bytes'] === 2) {
                $desc = $this->resolve(((array) $this->resolve($font['DescendantFonts'] ?? []))[0] ?? null);
                if (is_array($desc)) {
                    $info['dw'] = (float) ($this->resolve($desc['DW'] ?? 1000) ?: 1000);
                    $w = (array) $this->resolve($desc['W'] ?? []);
                    for ($i = 0; $i < count($w); ) {
                        $first = (int) $this->resolve($w[$i]); $next = $this->resolve($w[$i + 1] ?? null);
                        if (is_array($next) && !isset($next['__name'])) { foreach ($next as $k => $v) $info['widths'][$first + $k] = (float) $this->resolve($v); $i += 2; }
                        else { $last = (int) $next; $v = (float) $this->resolve($w[$i + 2] ?? 0); for ($c = $first; $c <= $last && $c - $first < 65536; $c++) $info['widths'][$c] = $v; $i += 3; }
                    }
                }
            } else {
                $firstChar = (int) $this->resolve($font['FirstChar'] ?? 0);
                foreach ((array) $this->resolve($font['Widths'] ?? []) as $k => $v) $info['widths'][$firstChar + $k] = (float) $this->resolve($v);
            }
            $cmap = $this->resolve($font['ToUnicode'] ?? null);
            if (is_array($cmap)) $info['map'] = $this->cmap($this->streamData($cmap), $info);
        }
        return $this->fonts[$key] = $info;
    }

    private function cmap(string $data, array &$info): array
    {
        $map = [];
        $hex = fn ($h) => hexdec($h);
        $uni = function ($h) { $b = @hex2bin(strlen($h) % 2 ? $h . '0' : $h); return $b === false ? '' : (string) @mb_convert_encoding($b, 'UTF-8', 'UTF-16BE'); };
        if (preg_match('/begincodespacerange\s*<([0-9a-fA-F]+)>/', $data, $cs)) $info['bytes'] = max(1, intdiv(strlen($cs[1]), 2));
        preg_match_all('/beginbfchar(.*?)endbfchar/s', $data, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all('/<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]*)>/', $block, $pairs, PREG_SET_ORDER);
            foreach ($pairs as $pair) $map[$hex($pair[1])] = $uni($pair[2]);
        }
        preg_match_all('/beginbfrange(.*?)endbfrange/s', $data, $blocks);
        foreach ($blocks[1] as $block) {
            preg_match_all('/<([0-9a-fA-F]+)>\s*<([0-9a-fA-F]+)>\s*(<[0-9a-fA-F]+>|\[[^\]]*\])/', $block, $ranges, PREG_SET_ORDER);
            foreach ($ranges as [, $lo, $hi, $dst]) {
                $lo = $hex($lo); $hi = min($hex($hi), $lo + 65535);
                if ($dst[0] === '[') {
                    preg_match_all('/<([0-9a-fA-F]+)>/', $dst, $list);
                    foreach ($list[1] as $i => $h) $map[$lo + $i] = $uni($h);
                } else {
                    $h = trim($dst, '<>'); $base = $hex(substr($h, -4)); $prefix = substr($h, 0, -4);
                    for ($c = $lo; $c <= $hi; $c++) $map[$c] = $uni($prefix . str_pad(dechex($base + $c - $lo), 4, '0', STR_PAD_LEFT));
                }
            }
        }
        if ($map) $info['winansi'] = false;
        return $map;
    }

    /** @return array{0:string,1:float,2:int,3:int} text, glyph width units, glyph count, single-byte spaces */
    private function decode(string $bytes, array $font): array
    {
        $out = ''; $step = $font['bytes']; $width = 0.0; $count = 0; $spaces = 0;
        for ($i = 0, $n = strlen($bytes); $i + $step <= $n; $i += $step) {
            $code = $step === 2 ? (ord($bytes[$i]) << 8) | ord($bytes[$i + 1]) : ord($bytes[$i]);
            $out .= $font['map'][$code] ?? ($step === 1 ? (string) @mb_convert_encoding($bytes[$i], 'UTF-8', 'Windows-1252') : '');
            $width += $font['widths'][$code] ?? ($step === 2 ? $font['dw'] : 500);
            $count++;
            if ($step === 1 && $code === 32) $spaces++;
        }
        return [$out, $width, $count, $spaces];
    }

    /** Text runs [x, y, text] from a content stream; follows q/Q/cm and Form XObjects. */
    private function runs(string $data, array $fontDict, array $resources, int $depth): array
    {
        $runs = []; $stack = []; $operands = [];
        $ctm = [1, 0, 0, 1, 0, 0]; $tm = $tlm = [1, 0, 0, 1, 0, 0]; $leading = 0; $size = 1.0; $tc = 0.0; $tw = 0.0; $th = 1.0; $font = ['bytes' => 1, 'map' => [], 'winansi' => true, 'widths' => [], 'dw' => 500];
        $mul = fn ($a, $b) => [$a[0] * $b[0] + $a[1] * $b[2], $a[0] * $b[1] + $a[1] * $b[3], $a[2] * $b[0] + $a[3] * $b[2], $a[2] * $b[1] + $a[3] * $b[3], $a[4] * $b[0] + $a[5] * $b[2] + $b[4], $a[4] * $b[1] + $a[5] * $b[3] + $b[5]];
        // Shows glyphs: records the run with its start/end x and font size on the page, then advances the text matrix.
        $show = function (array $parts) use (&$runs, &$tm, &$ctm, &$size, &$tc, &$tw, &$th, &$font, $mul) {
            $start = $mul($tm, $ctm); $text = ''; $advance = 0.0;
            foreach ($parts as $part) {
                if (is_string($part)) {
                    [$t, $w, $count, $spaces] = $this->decode($part, $font);
                    $text .= $t; $advance += ($w / 1000 * $size + $count * $tc + $spaces * $tw) * $th;
                } else {
                    $advance -= $part / 1000 * $size * $th;
                    if ($part < -180) $text .= ' ';
                }
            }
            $tm = $mul([1, 0, 0, 1, $advance, 0], $tm);
            if (trim($text) === '') return;
            $end = $mul($tm, $ctm);
            $scale = sqrt($start[0] ** 2 + $start[1] ** 2) ?: 1;
            $runs[] = [$start[4], $start[5], $text, $end[4], $size * $scale];
        };
        $num = fn ($v) => is_int($v) || is_float($v) ? (float) $v : 0.0;
        $p = 0; $n = strlen($data);
        while (true) {
            $p = $this->skipSpace($data, $p);
            if ($p >= $n) break;
            $c = $data[$p];
            if (strpos('/[(<+-.0123456789', $c) !== false) {
                $operands[] = $this->parse($data, $p);
                continue;
            }
            if (!preg_match('/\G([A-Za-z\'"*]+)/', $data, $m, 0, $p)) { $p++; continue; }
            $op = $m[1]; $p += strlen($op);
            switch ($op) {
                case 'BI': $end = strpos($data, 'EI', $p); $p = $end === false ? $n : $end + 2; break;
                case 'q': $stack[] = $ctm; break;
                case 'Q': $ctm = array_pop($stack) ?? [1, 0, 0, 1, 0, 0]; break;
                case 'cm': if (count($operands) >= 6) $ctm = $mul(array_map($num, array_slice($operands, -6)), $ctm); break;
                case 'BT': $tm = $tlm = [1, 0, 0, 1, 0, 0]; break;
                case 'Tf': $name = $operands[count($operands) - 2]['__name'] ?? null; if ($name !== null) $font = $this->font($name, $fontDict); $size = $num(end($operands)); break;
                case 'Tc': $tc = $num(end($operands)); break;
                case 'Tw': $tw = $num(end($operands)); break;
                case 'Tz': $th = $num(end($operands)) / 100; break;
                case 'TL': $leading = $num(end($operands)); break;
                case 'Tm': if (count($operands) >= 6) $tm = $tlm = array_map($num, array_slice($operands, -6)); break;
                case 'Td': case 'TD':
                    [$tx, $ty] = array_map($num, array_slice($operands, -2) + [0, 0]);
                    if ($op === 'TD') $leading = -$ty;
                    $tlm = $mul([1, 0, 0, 1, $tx, $ty], $tlm); $tm = $tlm; break;
                case 'T*': $tlm = $mul([1, 0, 0, 1, 0, -$leading], $tlm); $tm = $tlm; break;
                case "'": case '"':
                    $tlm = $mul([1, 0, 0, 1, 0, -$leading], $tlm); $tm = $tlm;
                    $s = end($operands); if (is_array($s) && isset($s['__str'])) $show([$s['__str']]); break;
                case 'Tj': $s = end($operands); if (is_array($s) && isset($s['__str'])) $show([$s['__str']]); break;
                case 'TJ':
                    $parts = [];
                    foreach ((array) end($operands) as $part) {
                        if (is_array($part) && isset($part['__str'])) $parts[] = $part['__str'];
                        elseif (is_int($part) || is_float($part)) $parts[] = (float) $part;
                    }
                    $show($parts); break;
                case 'Do':
                    $name = end($operands)['__name'] ?? null;
                    $xobjects = (array) $this->resolve($resources['XObject'] ?? []);
                    $form = $name !== null ? $this->resolve($xobjects[$name] ?? null) : null;
                    if ($depth < 3 && is_array($form) && ($form['Subtype']['__name'] ?? '') === 'Form') {
                        $res = (array) $this->resolve($form['Resources'] ?? []) ?: $resources;
                        foreach ($this->runs($this->streamData($form), (array) $this->resolve($res['Font'] ?? []) ?: $fontDict, $res, $depth + 1) as $run) {
                            $runs[] = [$run[0] + $ctm[4], $run[1] + $ctm[5], $run[2], $run[3] + $ctm[4], $run[4]];
                        }
                    }
                    break;
            }
            $operands = [];
        }
        return $runs;
    }

    /** Groups runs into lines top to bottom, each line left to right. */
    private function layout(array $runs): string
    {
        if (!$runs) return '';
        usort($runs, fn ($a, $b) => $b[1] <=> $a[1] ?: $a[0] <=> $b[0]);
        $lines = []; $current = []; $y = null;
        foreach ($runs as $run) {
            if ($y !== null && abs($run[1] - $y) > 3) { $lines[] = $current; $current = []; }
            if (!$current) $y = $run[1];
            $current[] = $run;
        }
        $lines[] = $current;
        $out = [];
        foreach ($lines as $line) {
            usort($line, fn ($a, $b) => $a[0] <=> $b[0]);
            $text = ''; $end = null;
            foreach ($line as $run) {
                // A gap wider than about a fifth of the font size between runs is a word break.
                $gap = $end === null ? 0 : $run[0] - $end;
                $text .= ($text === '' || str_ends_with($text, ' ') || str_starts_with($run[2], ' ') || $gap < 0.12 * $run[4] ? '' : ' ') . $run[2];
                $end = max($end ?? $run[3], $run[3]);
            }
            $out[] = trim(preg_replace('/[ \t\x{00A0}]+/u', ' ', $text));
        }
        return implode("\n", array_filter($out, fn ($l) => $l !== ''));
    }
}
