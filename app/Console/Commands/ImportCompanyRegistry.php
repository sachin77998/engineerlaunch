<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportCompanyRegistry extends Command
{
    protected $signature = 'companies:import-registry {file : CSV exported from the MCA company master data} {--limit=0 : Maximum records to add; 0 imports all}';

    protected $description = 'Import real Indian registry companies without changing existing employer and job data';

    public function handle(): int
    {
        $path = (string) $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error('CSV file is not readable: '.$path);
            return self::FAILURE;
        }

        $handle = fopen($path, 'rb');
        if ($handle === false) return self::FAILURE;

        try {
            $header = fgetcsv($handle, 0, ',', '"', '');
            if (! $header) throw new \RuntimeException('CSV header is missing.');
            $header = array_map(fn ($value) => Str::of((string) $value)->lower()->trim()->replace(' ', '_')->toString(), $header);
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
            $nameColumn = $this->column($header, ['company_name', 'companyname', 'name']);
            $cinColumn = $this->column($header, ['cin', 'corporate_identification_number']);
            if ($nameColumn === null || $cinColumn === null) {
                throw new \RuntimeException('CSV must have company_name and cin columns.');
            }
            $statusColumn = $this->column($header, ['company_status', 'status']);
            $stateColumn = $this->column($header, ['state', 'registered_state']);
            $industryColumn = $this->column($header, ['company_industrial_classification', 'principal_business_activity', 'industry']);
            $source = 'https://www.data.gov.in/catalog/company-master-data';
            $limit = max(0, (int) $this->option('limit'));
            $created = $matched = $skipped = 0;

            while (($row = fgetcsv($handle, 0, ',', '"', '')) !== false) {
                if (count($row) !== count($header)) { $skipped++; continue; }
                $name = trim((string) $row[$nameColumn]);
                $cin = strtoupper(trim((string) $row[$cinColumn]));
                $status = $statusColumn === null ? null : trim((string) $row[$statusColumn]);
                if ($name === '' || strlen($name) > 255 || ! preg_match('/^[A-Z0-9]{21}$/', $cin)) { $skipped++; continue; }
                if ($status && strcasecmp($status, 'Active') !== 0) { $skipped++; continue; }

                $existing = Company::where('registry_cin', $cin)->orWhere('name', $name)->first();
                if ($existing) {
                    if (! $existing->registry_cin) {
                        $existing->forceFill([
                            'registry_cin' => $cin,
                            'registry_status' => $status ?: null,
                            'registered_state' => $stateColumn === null ? null : trim((string) $row[$stateColumn]),
                            'registry_source_url' => $source,
                        ])->save();
                    }
                    $matched++;
                    continue;
                }

                $slug = Str::slug($name);
                if ($slug === '') { $skipped++; continue; }
                if (Company::where('slug', $slug)->exists()) $slug .= '-'.strtolower($cin);
                Company::create([
                    'name' => $name,
                    'slug' => $slug,
                    'country' => 'India',
                    'industry' => $industryColumn === null ? null : Str::limit(trim((string) $row[$industryColumn]), 255, ''),
                    'is_active' => true,
                    'sync_enabled' => false,
                    'registry_cin' => $cin,
                    'registry_status' => $status ?: null,
                    'registered_state' => $stateColumn === null ? null : trim((string) $row[$stateColumn]),
                    'registry_source_url' => $source,
                ]);
                $created++;
                if ($limit && $created >= $limit) break;
            }

            $this->info("Created {$created}; matched {$matched}; skipped {$skipped}.");
            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        } finally {
            fclose($handle);
        }
    }

    private function column(array $header, array $aliases): ?int
    {
        foreach ($aliases as $alias) {
            $position = array_search($alias, $header, true);
            if ($position !== false) return $position;
        }
        return null;
    }
}
