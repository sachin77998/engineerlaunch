<?php
namespace App\Services\Insights;
use Illuminate\Support\Collection;
use Carbon\Carbon;
class VendorPerformance {
 public function summarize(Collection $rows, ?Carbon $asOf=null): array {
 $asOf=($asOf??now())->copy()->startOfDay();
 $rows=$rows->filter(fn($r)=>$r->issued_on->lte($asOf));
 $completed=$rows->filter(fn($r)=>$r->completed_on && $r->completed_on->lte($asOf));
 $overdue=$rows->filter(fn($r)=>(!$r->completed_on || $r->completed_on->gt($asOf)) && $r->due_on->lt($asOf));
 $delays=$completed->map(fn($r)=>max(0,$r->due_on->diffInDays($r->completed_on,false)));
 $eligible=$completed->count()+$overdue->count();
 return ['count'=>$rows->count(),'completed'=>$completed->count(),'open_overdue'=>$overdue->count(),
 'average_term'=>$rows->isEmpty()?null:round($rows->avg(fn($r)=>$r->issued_on->diffInDays($r->due_on)),1),
 'average_days'=>$completed->isEmpty()?null:round($completed->avg(fn($r)=>$r->issued_on->diffInDays($r->completed_on)),1),
 'average_delay'=>$delays->isEmpty()?null:round($delays->avg(),1),
 'on_time'=>$eligible?round(100*$delays->filter(fn($d)=>$d===0)->count()/$eligible,1):null,
 'quality_accepted'=>$completed->where('quality','accepted')->count(),
 'quality_known'=>$completed->whereIn('quality',['accepted','rejected'])->count(),
 'status'=>$eligible<5?'Insufficient history':'Observed performance only'];
 }
}
