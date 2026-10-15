<?php

namespace App\Services\Insights;

use App\Models\Company;
use App\Models\CompanyAuditor;
use App\Models\CompanyFiling;
use App\Models\CompanyFinancialStatement;
use App\Models\CompanyOfficer;
use App\Models\CompanyProduct;
use App\Models\CompanyRelatedParty;
use App\Models\CompanyRegistryDetail;
use App\Models\CompanyShareholding;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Stores an uploaded MCA filing and saves what is read from it into the insight tables:
 * AOC-4 XBRL instance (XML, two years of statements), AOC-4 / financial-statement text, or MGT-7 annual return.
 */
class FilingImporter
{
    public function __construct(private FilingParser $parser, private XbrlParser $xbrl) {}

    public function import(Company $company, string $form, string $text, ?UploadedFile $file = null, ?int $userId = null): CompanyFiling
    {
        $form = XbrlParser::looksLikeXbrl($text) ? 'AOC-4 XBRL' : strtoupper($form);
        $dir = 'company-filings/' . $company->id;
        $filing = CompanyFiling::create([
            'company_id' => $company->id, 'form' => $form, 'original_name' => $file?->getClientOriginalName(),
            'file_path' => $file?->store($dir), 'uploaded_by' => $userId, 'status' => 'uploaded',
        ]);
        $textPath = $dir . '/filing-' . $filing->id . (XbrlParser::looksLikeXbrl($text) ? '.xml' : '.txt');
        Storage::put($textPath, $text);
        $filing->update(['text_path' => $textPath]);
        return $this->parse($filing, $text);
    }

    /** (Re)reads a stored filing; used after upload and when the parser improves. */
    public function parse(CompanyFiling $filing, ?string $text = null): CompanyFiling
    {
        $text ??= $filing->text_path && Storage::exists($filing->text_path) ? Storage::get($filing->text_path) : '';
        if (trim($text) === '') {
            $filing->update(['status' => 'failed', 'message' => 'No text could be read from this file. Scanned (image-only) PDFs cannot be read; upload the XBRL instance (.xml) or a text version instead.']);
            return $filing;
        }
        $company = $filing->company;
        if ($filing->form === 'RATING REPORT') {
            $evidence = app(RatingReportParser::class)->parse($text, $company->name);
            $filing->update(['parsed_fields'=>$evidence, 'status'=>'review', 'message'=>$evidence['subject_matches']
                ? 'Report stored for review. Agency authenticity and current facility rating have not been verified.'
                : 'Company heading does not match. Do not use this report for this vendor until identity is resolved.']);
            return $filing->refresh();
        }
        if (preg_match('/(?:ICRA|CARE Ratings|careedge)/i', mb_substr($text, 0, 7000)) && preg_match('/(?:Summary of rating action|Rating Action)/i', mb_substr($text, 0, 7000))) {
            $filing->update(['status'=>'review','message'=>'This looks like a rating-agency report. Upload it as RATING REPORT; no financial figures were imported.']);
            return $filing->refresh();
        }
        $isXbrl = XbrlParser::looksLikeXbrl($text);
        $isMgt7 = !$isXbrl && str_starts_with($filing->form, 'MGT');
        try {
            $data = $isXbrl ? $this->xbrl->parse($text) : ($isMgt7 ? $this->parser->parseMgt7($text) : $this->parser->parseAoc4($text));
        } catch (\Throwable $e) {
            $filing->update(['status' => 'failed', 'message' => $e->getMessage()]);
            return $filing;
        }
        // Year-wise figures: XBRL has several years; text filings have one.
        $years = $isXbrl ? $data['years'] : [];
        if (!$isXbrl && !$isMgt7 && $data['financial_year']) {
            $figures = array_intersect_key($data, array_flip(array_keys(FilingParser::AOC4_LABELS)));
            if ($figures) {
                if (isset($figures['profit_before_tax'], $figures['finance_cost'], $figures['depreciation'])) $figures['ebitda'] = $figures['profit_before_tax'] + $figures['finance_cost'] + $figures['depreciation'];
                if (isset($figures['long_term_borrowings']) || isset($figures['short_term_borrowings'])) $figures['total_debt'] = ($figures['long_term_borrowings'] ?? 0) + ($figures['short_term_borrowings'] ?? 0);
                if (!isset($figures['net_worth']) && (isset($figures['share_capital']) || isset($figures['reserves']))) $figures['net_worth'] = ($figures['share_capital'] ?? 0) + ($figures['reserves'] ?? 0);
                $years[$data['financial_year']] = $figures + ['period_end' => $data['period_end']];
            }
        }
        $data['financial_year'] ??= $years ? array_key_first($years) : null;
        $data['all_fields'] = $isXbrl ? [] : $this->parser->fields($text);

        $warnings = [];
        if ($data['cin'] && ($known = $company->registryDetail?->cin ?? $company->registry_cin) && $known !== $data['cin']) {
            $warnings[] = "The filing is for CIN {$data['cin']}, but this company is recorded as {$known}.";
            $filing->update(['parsed_fields'=>$data, 'status'=>'review', 'message'=>implode(' ', $warnings).' No company records were changed.']);
            return $filing->refresh();
        }
        if (!$isMgt7 && !$years) $warnings[] = $data['financial_year'] ? 'No financial figures were recognised in this file.' : 'The financial year could not be read.';

        DB::transaction(function () use ($company, $filing, $data, $years, $isMgt7, $isXbrl, &$warnings) {
            $year = $data['financial_year'];
            $registry = CompanyRegistryDetail::firstOrNew(['company_id' => $company->id]);
            if ($data['cin'] && !$registry->cin && !CompanyRegistryDetail::where('cin', $data['cin'])->where('company_id', '!=', $company->id)->exists()) $registry->cin = $data['cin'];
            if ($data['cin'] && !$company->registry_cin) $company->registry_cin = $data['cin'];

            foreach ($years as $fy => $figures) {
                $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => $fy, 'is_consolidated' => (bool) ($data['is_consolidated'] ?? false)]);
                // A filing never erases figures: values it lacks keep what an earlier filing or hand entry saved.
                foreach ($figures as $field => $value) if ($value !== null) $statement->{$field} = $value;
                $statement->source = $filing->form;
                $statement->filing_id = $filing->id;
                $statement->save();
                $registry->last_balance_sheet_date = max((string) $registry->last_balance_sheet_date?->toDateString(), (string) ($figures['period_end'] ?? '')) ?: null;
            }
            if (!empty($data['activities'])) {
                CompanyProduct::where('company_id', $company->id)->where('financial_year', $year)->delete();
                foreach ($data['activities'] as $activity) {
                    CompanyProduct::create(['company_id' => $company->id, 'financial_year' => $year, 'product_code' => $activity['code'],
                        'description' => trim($activity['description'] . ' (' . $activity['main_activity'] . ')'), 'turnover_percentage' => $activity['turnover_percentage']]);
                }
            }
            if (!empty($data['related_parties'])) {
                CompanyRelatedParty::where('company_id', $company->id)->where('financial_year', $year)->delete();
                foreach ($data['related_parties'] as $party) CompanyRelatedParty::create(['company_id' => $company->id, 'financial_year' => $year] + $party);
            }
            foreach ($data['auditors'] ?? [] as $auditor) {
                CompanyAuditor::updateOrCreate(['company_id' => $company->id, 'financial_year' => $year, 'name' => $auditor['name']], array_filter($auditor) + ['source' => $filing->form]);
            }
            foreach ($data['officers'] ?? [] as $officer) {
                CompanyOfficer::updateOrCreate(['company_id' => $company->id, 'name' => $officer['name'], 'designation' => $officer['designation']],
                    array_filter(['din' => $officer['din'] ?? null, 'pan' => $officer['pan'] ?? null, 'appointed_on' => $officer['appointed_on'] ?? null,
                        'ceased_on' => $officer['ceased_on'] ?? null, 'shares_held' => $officer['shares_held'] ?? null], fn ($v) => $v !== null)
                    + ['source' => $filing->form . ' ' . ($year ?? '')]);
            }
            if (!empty($data['shareholdings'])) {
                CompanyShareholding::where('company_id', $company->id)->where('financial_year', $year)->delete();
                foreach ($data['shareholdings'] as $holding) CompanyShareholding::create(['company_id' => $company->id, 'financial_year' => $year, 'source' => $filing->form] + $holding);
            }
            if ($isMgt7) {
                foreach (['authorised_capital', 'paid_up_capital', 'email', 'members_count', 'registered_address', 'pan', 'incorporation_date', 'last_agm_date', 'company_class', 'company_category', 'company_subcategory'] as $field) {
                    if (isset($data[$field]) && $data[$field] !== '') $registry->{$field} = $data[$field];
                }
                if (!empty($data['website']) && !$company->website) $company->website = rtrim($data['website'], '/');
                // MGT-7 states turnover and net worth; they fill the year only where no AOC-4 figure exists.
                if ($year && ($data['turnover'] !== null || $data['net_worth'] !== null || !empty($data['shares_outstanding']))) {
                    $statement = CompanyFinancialStatement::firstOrNew(['company_id' => $company->id, 'financial_year' => $year, 'is_consolidated' => false]);
                    $statement->revenue ??= $data['turnover'];
                    $statement->net_worth ??= $data['net_worth'];
                    $statement->shares_outstanding ??= $data['shares_outstanding'] ?? null;
                    $statement->source ??= $filing->form;
                    $statement->save();
                }
            } elseif (!empty($data['company_category']) && !$registry->company_category) {
                $registry->company_category = $data['company_category'];
            }
            if ($company->isDirty()) $company->save();
            $registry->source = $registry->source ?: 'MCA filings';
            $registry->save();
            $filing->update(['financial_year' => $year, 'parsed_fields' => $data, 'status' => $warnings ? 'review' : 'parsed', 'message' => $warnings ? implode(' ', $warnings) : null]);
        });
        return $filing->refresh();
    }
}
