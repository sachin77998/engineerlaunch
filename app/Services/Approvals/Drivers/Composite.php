<?php

namespace App\Services\Approvals\Drivers;

/** Several readers for one state (e.g. NSWS approvals plus the state portal's policies); a failing reader is skipped. */
class Composite
{
    /** @var object[] */
    private array $drivers;
    public array $errors = [];

    public function __construct(object $source, array $classes)
    {
        $this->drivers = array_map(fn ($c) => new $c($source), $classes);
    }

    public function services(): array
    {
        $out = [];
        foreach ($this->drivers as $i => $d) {
            try {
                foreach ($d->services() as $s) $out[] = $s + ['_driver' => $i];
            } catch (\Throwable $e) {
                $this->errors[] = class_basename($d) . ': ' . mb_substr($e->getMessage(), 0, 160);
            }
        }
        return $out;
    }

    public function documents(array $service): array
    {
        return $this->drivers[$service['_driver'] ?? 0]->documents($service);
    }

    public function pdf(string $url): ?string
    {
        return $this->drivers[0]->pdf($url);
    }

    public function policies(): array
    {
        $out = [];
        foreach ($this->drivers as $d) {
            try { array_push($out, ...$d->policies()); } catch (\Throwable $e) { $this->errors[] = class_basename($d) . ': ' . mb_substr($e->getMessage(), 0, 160); }
        }
        return $out;
    }
}
