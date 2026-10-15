<?php
namespace Tests\Feature;

use App\Models\Company;
use App\Models\CompanyFiling;
use App\Models\CompanyFinancialStatement;
use App\Services\Insights\VendorAssessment;
use App\Services\Insights\RatingReportParser;
use App\Services\Insights\FilingImporter;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class VendorAssessmentTest extends TestCase
{
    private function company(array $statements = []): Company
    {
        $company = new Company(['name'=>'GNA Drivelines Limited','registry_cin'=>'U12345PB2000PLC123456']);
        $company->setRelation('filings',collect());
        $company->setRelation('registryDetail',null);
        $company->setRelation('financialStatements',collect($statements)->map(fn($s)=>new CompanyFinancialStatement($s + ['is_consolidated'=>false])));
        return $company;
    }

    public function test_stress_scenario_is_explicit_and_missing_inputs_stay_unknown(): void
    {
        $assessment=app(VendorAssessment::class);
        $result=$assessment->assess($this->company([['financial_year'=>'2025-26','revenue'=>1000,'profit_before_tax'=>100,'finance_cost'=>20]]),-10);
        $this->assertSame(900.0,$result['scenario']['revenue']);
        $this->assertSame(20.0,$result['scenario']['ebit']);
        $this->assertSame(1.0,$result['scenario']['interest_cover']);
        $missing=$assessment->assess($this->company(),-10);
        $this->assertNull($missing['scenario']['revenue']);
        $this->assertNull($missing['scenario']['interest_cover']);
    }

    public function test_rating_report_mislabelled_aoc4_is_not_imported(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        Schema::create('companies',fn(Blueprint $t)=>$t->id());
        Schema::create('company_filings',function(Blueprint $t){$t->id();$t->string('form');$t->string('status');$t->json('parsed_fields')->nullable();$t->text('message')->nullable();$t->timestamps();});
        $filing=CompanyFiling::create(['form'=>'AOC-4','status'=>'uploaded']);
        $filing->setRelation('company',$this->company());
        $result=app(FilingImporter::class)->parse($filing,'CARE Ratings GNA Drivelines Limited Rating Action Withdrawn');
        $this->assertSame('review',$result->status);
        $this->assertStringContainsString('no financial figures were imported',$result->message);
    }
    public function test_missing_inputs_never_become_zero_or_an_approval(): void
    {
        $result = app(VendorAssessment::class)->assess($this->company([['financial_year'=>'2025-26','profit_before_tax'=>100,'long_term_borrowings'=>50,'net_worth'=>100]]));
        foreach ($result['metrics'] as $metric) $this->assertNull($metric['value']);
        $this->assertStringContainsString('not established',$result['decision']);
        $this->assertSame('Missing',$result['checks'][0]['status']);
    }

    public function test_consolidated_figures_do_not_stand_in_for_vendor_and_year_gaps_are_not_yoy(): void
    {
        $result = app(VendorAssessment::class)->assess($this->company([
            ['financial_year'=>'2022-23','revenue'=>100],
            ['financial_year'=>'2024-25','revenue'=>200],
            ['financial_year'=>'2025-26','revenue'=>900,'is_consolidated'=>true],
        ]));
        $this->assertSame('2024-25',$result['year']);
        $this->assertNull($result['trends']->last()['growth']);
        $this->assertCount(2,$result['trends']);
    }

    public function test_ratios_and_trend_are_calculated_with_risk_reasons(): void
    {
        $result = app(VendorAssessment::class)->assess($this->company([
            ['financial_year'=>'2024-25','revenue'=>100],
            ['financial_year'=>'2025-26','revenue'=>120,'current_assets'=>50,'current_liabilities'=>100,'profit_before_tax'=>-5,'finance_cost'=>10,'profit_after_tax'=>-8,'total_debt'=>100,'net_worth'=>50],
        ]));
        $this->assertSame([0.5,2.0,0.5,null],array_column($result['metrics'],'value'));
        $this->assertSame(20.0,$result['trends']->last()['growth']);
        $this->assertContains('Reported EBIT does not cover finance cost.',$result['risks']);
    }

    public function test_rating_withdrawal_is_retained_without_promoting_historical_rating(): void
    {
        $text = "Press Release\nGNA Drivelines Limited\nSeptember 26, 2023\nFacilities/Instruments Amount Rating Action\nLong Term Bank Facilities - - Withdrawn\nShort Term Bank Facilities - - Withdrawn\nRationale\nCARE has withdrawn the outstanding rating.\fRating history CARE BB+ Stable";
        $report = app(RatingReportParser::class)->parse($text,'GNA Drivelines Limited');
        $this->assertTrue($report['subject_matches']);
        $this->assertTrue($report['withdrawal_mentioned']);
        $this->assertSame('2023-09-26',$report['report_date']);
        $this->assertStringNotContainsString('BB+',$report['summary_excerpt']);
        $this->assertSame('unverified_upload',$report['verification']);
        $this->assertFalse(app(RatingReportParser::class)->parse($text,'DRAS Engineering Private Limited')['subject_matches']);
    }

    public function test_rating_subject_is_matched_in_heading_not_group_company_in_rationale(): void
    {
        $text="December 02, 2025\nDRAS Engineering Private Limited: Long-term rating upgraded to [ICRA]A(Stable)\nSummary of rating action\nRationale\nInternational Tractors Limited supports the company.";
        $this->assertTrue(app(RatingReportParser::class)->parse($text,'DRAS Engineering Private Limited')['subject_matches']);
        $this->assertFalse(app(RatingReportParser::class)->parse($text,'International Tractors Limited')['subject_matches']);
    }

    public function test_cin_mismatch_stops_before_any_financial_or_registry_write(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        Schema::create('companies',fn(Blueprint $t)=>$t->id());
        Schema::create('company_filings',function(Blueprint $t){$t->id();$t->string('form');$t->string('status');$t->json('parsed_fields')->nullable();$t->text('message')->nullable();$t->timestamps();});
        $filing = CompanyFiling::create(['form'=>'AOC-4','status'=>'uploaded']);
        $filing->setRelation('company',$this->company());
        $result = app(FilingImporter::class)->parse($filing,"CIN U12345PB2001PLC654321 Financial year ended 31/03/2025 Revenue from operations 1000");
        $this->assertSame('review',$result->status);
        $this->assertStringContainsString('No company records were changed',$result->message);
        // Financial and registry tables deliberately do not exist: any write would fail this test.
    }

    public function test_rating_upload_does_not_enter_financial_parser_and_view_escapes_evidence(): void
    {
        config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']); DB::purge('sqlite');
        Schema::create('companies',fn(Blueprint $t)=>$t->id());
        Schema::create('company_filings',function(Blueprint $t){$t->id();$t->string('form');$t->string('status');$t->json('parsed_fields')->nullable();$t->text('message')->nullable();$t->timestamps();});
        $company = $this->company();
        $filing = CompanyFiling::create(['form'=>'RATING REPORT','status'=>'uploaded']);
        $filing->setRelation('company',$company);
        $result = app(FilingImporter::class)->parse($filing,"GNA Drivelines Limited September 26, 2023\nFacilities/Instruments Withdrawn\nCARE Ratings <script>alert(1)</script>");
        $this->assertSame('review',$result->status);
        $company->setRelation('filings',collect([$result]));
        $html=view('insights.partials.vendor-assessment',['vendorAssessment'=>app(VendorAssessment::class)->assess($company),'money'=>fn($v)=>$v ?? 'Unknown','isOwner'=>false])->render();
        $this->assertStringContainsString('Vendor checks',$html);
        $this->assertStringNotContainsString('<script>',$html);
        $this->assertStringContainsString('Withdrawal appears',$html);
    }
}
