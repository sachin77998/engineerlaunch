<?php
namespace App\Services\Insights;
use Illuminate\Support\Collection;
/** Experimental k-nearest-neighbour delay model, evaluated on a chronological holdout.
 * No prediction is released unless it beats a training-only median baseline.
 */
class PaymentDelayModel {
 private function features($r): array {
 return [log1p((float)$r->amount)/10,$r->issued_on->diffInDays($r->due_on)/365];
 }
 private function target($r): float { return max(0,$r->due_on->diffInDays($r->completed_on,false)); }
 private function predict(Collection $training,array $features): float {
 return (float)$training->sortBy(function($r) use($features){$f=$this->features($r);return ($f[0]-$features[0])**2+($f[1]-$features[1])**2;})->take(5)->avg(fn($r)=>$this->target($r));
 }
 public function evaluate(Collection $records,?float $amount=null,?int $term=null): array {
 $result=['status'=>'Insufficient history','model'=>'KNN delay v1','training'=>0,'holdout'=>0,'mae'=>null,'baseline_mae'=>null,'prediction'=>null];
 $closed=$records->filter(fn($r)=>$r->completed_on && $r->completed_on->lte(now()))->sortBy('issued_on')->values();
 if($closed->count()<60 || $closed->pluck('company_id')->unique()->count()<5) return $result;
 $cutoff=$closed[(int)floor($closed->count()*0.7)]->issued_on;
 // An outcome must already have been known when the holdout period starts.
 $training=$closed->filter(fn($r)=>$r->issued_on->lt($cutoff) && $r->completed_on->lt($cutoff));
 $test=$closed->filter(fn($r)=>$r->issued_on->gte($cutoff));
 $result['training']=$training->count(); $result['holdout']=$test->count();
 if($training->count()<40 || $test->count()<15) return $result;
 $median=$training->map(fn($r)=>$this->target($r))->median();
 $result['mae']=round($test->avg(fn($r)=>abs($this->target($r)-$this->predict($training,$this->features($r)))),2);
 $result['baseline_mae']=round($test->avg(fn($r)=>abs($this->target($r)-$median)),2);
 $result['status']=$result['mae']<$result['baseline_mae']?'Experimental model passed holdout':'Model did not beat baseline';
 // Unpaid outcomes are censored: this estimates delay among completed payments only, never default probability.
 if($result['mae']<$result['baseline_mae'] && $amount!==null && $term!==null && $amount>0 && $term>=0) {
 $minAmount=$training->min('amount');$maxAmount=$training->max('amount');
 $terms=$training->map(fn($r)=>$r->issued_on->diffInDays($r->due_on));
 if($amount>=$minAmount && $amount<=$maxAmount && $term>=$terms->min() && $term<=$terms->max())
 $result['prediction']=round($this->predict($training,[log1p($amount)/10,$term/365]),1);
 }
 return $result;
 }
}
