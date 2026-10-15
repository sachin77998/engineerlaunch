<?php
namespace Tests\Feature;
use Tests\TestCase;
use App\Models\{Company,CompanyOfficer,VendorObservation,User};
use App\Services\Insights\{VendorPerformance,InsightsDashboard,PaymentDelayModel};
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Illuminate\Http\Request;
use App\Http\Controllers\VendorComparisonController;
class VendorWorkflowTest extends TestCase {
 public function test_no_history_produces_no_rating_or_prediction(): void {
 $r=app(VendorPerformance::class)->summarize(collect());
 $this->assertNull($r['on_time']);$this->assertSame('Insufficient history',$r['status']);
 $m=app(PaymentDelayModel::class)->evaluate(collect(),1000,90);
 $this->assertNull($m['prediction']);$this->assertSame('Insufficient history',$m['status']);
 }
 public function test_long_contract_is_on_time_and_unpaid_overdue_is_not_hidden(): void {
 $rows=collect([
 new VendorObservation(['issued_on'=>'2025-01-01','due_on'=>'2025-04-01','completed_on'=>'2025-04-01']),
 new VendorObservation(['issued_on'=>'2025-01-01','due_on'=>'2025-01-15','completed_on'=>'2025-02-01']),
 new VendorObservation(['issued_on'=>'2025-01-01','due_on'=>'2025-02-01']),
 new VendorObservation(['issued_on'=>'2025-01-01','due_on'=>'2030-02-01']),
 ]);
 $r=app(VendorPerformance::class)->summarize($rows,\Carbon\Carbon::parse('2025-05-01'));
 $this->assertSame(33.3,$r['on_time']);$this->assertSame(1,$r['open_overdue']);$this->assertSame(8.5,$r['average_delay']);
 }
 public function test_directors_exclude_other_officers_and_unknown_ownership_stays_unknown(): void {
 $c=new Company();$c->setRelation('officers',collect([
 new CompanyOfficer(['designation'=>'Director','appointed_on'=>'2020-01-01']),
 new CompanyOfficer(['designation'=>'Director','appointed_on'=>'2018-01-01','ceased_on'=>'2021-01-01']),
 new CompanyOfficer(['designation'=>'Company secretary','appointed_on'=>'2018-01-01']),
 ]));
 foreach(['shareholdings','financialStatements','filings'] as $relation)$c->setRelation($relation,collect());
 $r=app(InsightsDashboard::class)->build($c);
 $this->assertCount(1,$r['current']);$this->assertCount(1,$r['past']);$this->assertNull($r['latest_ownership']);
 $html=view('insights.partials.ownership-dashboard',['dashboard'=>$r])->render();
 $this->assertStringContainsString('Unknown',$html);$this->assertStringNotContainsString('100.00%',$html);
 }
 public function test_non_owner_cannot_import_evidence(): void {
 $r=Request::create('/','POST');$r->setUserResolver(fn()=>new User(['role'=>'candidate']));
 $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
 app(VendorComparisonController::class)->store($r);
 }
 public function test_duplicate_reference_does_not_overwrite_original(): void {
 config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:']);DB::purge('sqlite');
 Schema::create('companies',fn(Blueprint $t)=>$t->id());
 DB::table('companies')->insert(['id'=>1]);
 (require database_path('migrations/2026_10_15_000010_create_vendor_observations_table.php'))->up();
 $row=['company_id'=>1,'reference'=>'INV-1','kind'=>'vendor_refund','material'=>'steel','issued_on'=>'2025-01-01','due_on'=>'2025-02-01','amount'=>1000,'currency'=>'INR','evidence'=>'contract-1'];
 VendorObservation::create($row+['recorded_by'=>1]);
 $r=Request::create('/','POST',$row);$r->setUserResolver(fn()=>new User(['id'=>1,'role'=>'admin']));
 try { app(VendorComparisonController::class)->store($r);$this->fail('Duplicate accepted'); }
 catch(\Illuminate\Validation\ValidationException $e){$this->assertSame(1,VendorObservation::count());}
 }
 public function test_charts_render_losses_below_zero_and_do_not_replace_unknowns(): void {
 $r=['year'=>'2025-26','revenue'=>10000000,'pat'=>-5000000,'ebitda'=>null,'net_worth'=>null,'total_assets'=>null,'total_debt'=>null,'ebitda_margin'=>null,'net_margin'=>-50];
 $html=view('insights.partials.financial-charts',['ratios'=>collect([$r]),'money'=>fn($n)=>$n===null?'Unknown':$n])->render();
 $this->assertStringContainsString('-0.50 Cr',$html);$this->assertStringContainsString('-50.00 %',$html);$this->assertStringContainsString('Unknown',$html);
 }
}
