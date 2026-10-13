<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyFiling;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyOfficer;
use App\Models\CompanyProduct;
use App\Models\CompanyRelatedParty;
use App\Models\CompanyRegistryDetail;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/** Stores an uploaded AOC-4 / MGT-7 (PDF and its extracted text) and saves the figures read from it. */
class FilingImporter
{
    public function __construct(private FilingParser $parser) {}

    public function import(Company $company, string $form, string $text, ?UploadedFile $file = null, ?int $userId = null): CompanyFiling
    {
        $form = strtoupper($form);
        $dir = 'company-filings/' . $company->id;
        $filing = CompanyFiling::create([
            'company_id' => $company->id, 'form' => $form, 'original_name' => $file?->getClientOriginalName(),
            'file_path' => $file?->store($dir), 'uploaded_by' => $userId, 'status' => 'uploaded',
        ]);
        $textPath = $dir . '/filing-' . $filing->id . '.txt';
        Storage::put($textPath, $text);
        $filing->update(['text_path' => $textPath]);
        return $this->parse($filing, $text);
    }

    /** (Re)reads a stored filing; used after upload and when the parser improves. */
    public function parse(CompanyFiling $filing, ?string $text = null): CompanyFiling
    {
        $text ??= $filing->text_path && Storage::exists($filing->text_path) ? Storage::get($filing->text_path) : '';
        if (trim($text) === '') {
            $filing->update(['status' => 'failed', 'message' => 'No text could be read from this file. Scanned (image-only) PDFs need OCR first; upload the text version instead.']);
            return $filing;
        }
        $company = $filing->company;
        $isMgt7 = str_starts_with($filing->form, 'MGT');
        $data = $isMgt7 ? $this->parser->parseMgt7($text) : $this->parser->parseAoc4($text);
        $data['all_fields'] = $this->parser->fields($text);
        $warnings = [];
        if ($data['cin'] && ($known = $company->registryDetail?->cin ?? $company->registry_cin) && $known !== $data['cin']) {
            $warnings[] = "The filing is for CIN {$data['cin']}, but this company is recorded as {$known}.";
        }

        DB::transaction(function () use ($company, $filing, $data, $isMgt7, &$warnings) {
            $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
            if ($data['cin'] && !$registry->cin && !CompanyRegistryDetail::where('cin', $data['cin'])->where('company_id', '!=', $company->id)->exists()) $registry->cin = $data['cin'];
            if (!empty($data['activities'])) {
                CompanyProduct::where('company_id', $company->id)->where('financial_year', $data['financial_year'])->delete();
                foreach ($data['activities'] as $activity) {
                    CompanyProduct::create(['company_id' => $company->id, 'financial_year' => $data['financial_year'], 'product_code' => $activity['code'],
                        'description' => $activity['description'] . ' (' . $activity['main_activity'] . ')', 'turnover_percentage' => $activity['turnover_percentage']]);
                }
            }
            if (!empty($data['related_parties'])) {
                CompanyRelatedParty::where('company_id', $company->id)->where('financial_year', $data['financial_year'])->delete();
                foreach ($data['related_parties'] as $party) {
                    CompanyRelatedParty::create(['company_id' => $company->id, 'financial_year' => $data['financial_year']] + $party);
                }
            }
            foreach ($data['officers'] ?? [] as $officer) {
                CompanyOfficer::updateOrCreate(['company_id' => $company->id, 'name' => $officer['name'], 'designation' => $officer['designation']],
                    array_filter(['din' => $officer['din'], 'appointed_on' => $officer['appointed_on']]) + ['source' => $filing->form . ' ' . ($data['financial_year'] ?? '')]);
            }
            if ($isMgt7) {
                foreach (['authorised_capital', 'paid_up_capital', 'email', 'members_count', 'registered_address', 'pan'] as $field) {
                    if (isset($data[$field])) $registry->{$field} = $data[$field];
                }
                // MGT-7 also states turnover and net worth; they fill a year only where no AOC-4 figure exists.
                if ($data['financial_year'] && ($data['turnover'] !== null || $data['net_worth'] !== null)) {
                    $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => $data['financial_year'], 'is_consolidated' => false]);
                    $statement->revenue ??= $data['turnover'];
                    $statement->net_worth ??= $data['net_worth'];
                    $statement->source ??= $filing->form;
                    $statement->save();
                }
            } else {
                if (!empty($data['company_category']) && !$registry->company_category) $registry->company_category = $data['company_category'];
                $figures = array_intersect_key($data, array_flip(array_keys(FilingParser::AOC4_LABELS)));
                if (!$data['financial_year']) {
                    $warnings[] = 'The financial year could not be read; enter it and save again.';
                } elseif (!$figures) {
                    $warnings[] = 'No financial figures were recognised in this file.';
                } else {
                    if (isset($figures['profit_before_tax'], $figures['finance_cost'], $figures['depreciation'])) {
                        $figures['ebitda'] = $figures['profit_before_tax'] + $figures['finance_cost'] + $figures['depreciation'];
                    }
                    if (isset($figures['long_term_borrowings']) || isset($figures['short_term_borrowings'])) {
                        $figures['total_debt'] = ($figures['long_term_borrowings'] ?? 0) + ($figures['short_term_borrowings'] ?? 0);
                    }
                    if (!isset($figures['net_worth']) && (isset($figures['share_capital']) || isset($figures['reserves']))) {
                        $figures['net_worth'] = ($figures['share_capital'] ?? 0) + ($figures['reserves'] ?? 0);
                    }
                    CompanyFinancialStatement::updateOrCreate(
                        ['company_id' => $company->id, 'financial_year' => $data['financial_year'], 'is_consolidated' => $data['is_consolidated']],
                        $figures + ['period_end' => $data['period_end'], 'source' => $filing->form, 'filing_id' => $filing->id]);
                    $registry->last_balance_sheet_date = $data['period_end'] ?? $registry->last_balance_sheet_date;
                }
            }
            $registry->source = $registry->source ?: 'MCA filings';
            $registry->save();
            $filing->update(['financial_year' => $data['financial_year'], 'parsed_fields' => $data,
                'status' => $warnings ? 'review' : 'parsed', 'message' => $warnings ? implode(' ', $warnings) : null]);
        });
        return $filing->refresh();
    }
}
