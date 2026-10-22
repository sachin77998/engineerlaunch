<?php

namespace App\Services\Branding;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Throwable;

// Company, stock and fund-house logos: fetched once per website domain, stored on the public disk, served locally.
class Logos
{
    // Official domains of fund houses (AMFI names); unknown houses keep their initials badge.
    public const AMC_DOMAINS = [
        '360 one' => '360.one', 'aditya birla' => 'adityabirlacapital.com', 'angel one' => 'angelone.in', 'axis' => 'axismf.com', 'bajaj finserv' => 'bajajfinserv.in',
        'bandhan' => 'bandhanmutual.com', 'bank of india' => 'boimf.in', 'baroda bnp' => 'barodabnpparibasmf.in', 'canara robeco' => 'canararobeco.com', 'capitalmind' => 'capitalmind.in',
        'choice' => 'choiceindia.com', 'dsp' => 'dspim.com', 'edelweiss' => 'edelweissmf.com', 'franklin' => 'franklintempletonindia.com', 'groww' => 'groww.in', 'hdfc' => 'hdfcfund.com',
        'helios' => 'heliosmf.in', 'hsbc' => 'hsbc.co.in', 'icici' => 'icicipruamc.com', 'invesco' => 'invescomutualfund.com', 'iti' => 'itiamc.com', 'jio blackrock' => 'jioblackrockamc.com',
        'jm financial' => 'jmfinancialmf.com', 'kotak' => 'kotakmf.com', 'lic' => 'licmf.com', 'mahindra manulife' => 'mahindramanulife.com', 'mirae' => 'miraeassetmf.co.in',
        'motilal' => 'motilaloswalmf.com', 'navi' => 'navi.com', 'nippon' => 'nipponindiamf.com', 'nj ' => 'njmutualfund.com', 'old bridge' => 'oldbridgemf.com', 'pgim' => 'pgimindiamf.com',
        'ppfas' => 'ppfas.com', 'parag parikh' => 'ppfas.com', 'quantum' => 'quantumamc.com', 'quant ' => 'quantmutual.com', 'samco' => 'samcomf.com', 'sbi' => 'sbimf.com', 'shriram' => 'shriramamc.in',
        'sundaram' => 'sundarammutual.com', 'tata' => 'tatamutualfund.com', 'taurus' => 'taurusmutualfund.com', 'trust' => 'trustmf.com', 'union' => 'unionmf.com', 'uti' => 'utimf.com',
        'whiteoak' => 'whiteoakamc.com', 'zerodha' => 'zerodhafundhouse.com',
    ];

    public static function domain(?string $url): ?string
    {
        if (!$url || !trim($url)) return null;
        $host = parse_url(str_contains($url, '://') ? $url : 'https://' . trim($url), PHP_URL_HOST);
        $host = $host ? preg_replace('/^www\./', '', strtolower($host)) : null;
        return $host && str_contains($host, '.') && strlen($host) <= 190 ? $host : null;
    }

    public static function amcDomain(?string $name): ?string
    {
        $n = ' ' . strtolower((string) $name) . ' ';
        foreach (self::AMC_DOMAINS as $key => $domain) if (str_contains($n, ' ' . $key)) return $domain;
        return null;
    }

    /** Public URL of a stored logo, or null so callers show their initials badge. */
    public static function url(?string $domain): ?string
    {
        if (!$domain) return null;
        $map = Cache::remember('brand-logos:map', 600, fn () => rescue(fn () => DB::table('brand_logos')->where('status', 'ok')->pluck('path', 'domain')->all(), [], false));
        return isset($map[$domain]) ? asset('storage/' . $map[$domain]) : null;
    }

    /** Downloads the logo for a domain; returns true when a usable image (32px or larger) was stored. */
    public function fetch(string $domain): bool
    {
        $sources = ['https://www.google.com/s2/favicons?domain=' . rawurlencode($domain) . '&sz=128', 'https://icons.duckduckgo.com/ip3/' . rawurlencode($domain) . '.ico'];
        foreach ($sources as $src) {
            try {
                $r = Http::timeout(10)->withHeaders(['User-Agent' => 'Mozilla/5.0 (logo fetch)'])->get($src);
                if (!$r->successful() || strlen($r->body()) < 200) continue;
                $info = @getimagesizefromstring($r->body());
                if (!$info || $info[0] < 32) continue;
                $ext = ['image/png' => 'png', 'image/jpeg' => 'jpg', 'image/gif' => 'gif', 'image/webp' => 'webp', 'image/vnd.microsoft.icon' => 'ico', 'image/x-icon' => 'ico'][$info['mime']] ?? 'png';
                $path = 'logos/' . preg_replace('/[^a-z0-9.-]/', '', $domain) . '.' . $ext;
                Storage::disk('public')->put($path, $r->body());
                DB::table('brand_logos')->updateOrInsert(['domain' => $domain], ['path' => $path, 'status' => 'ok', 'checked_at' => now()]);
                return true;
            } catch (Throwable) {
                continue;
            }
        }
        DB::table('brand_logos')->updateOrInsert(['domain' => $domain], ['path' => null, 'status' => 'missing', 'checked_at' => now()]);
        return false;
    }
}
