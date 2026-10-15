<?php
namespace App\Services\Insights;
use App\Models\Company;

class InsightsDashboard
{
    public function build(Company $company): array
    {
        $directors=$company->officers->filter(fn($o)=>preg_match('/director/i',(string)$o->designation));
        $current=$directors->filter(fn($o)=>(!$o->ceased_on || $o->ceased_on->isFuture()) && (!$o->appointed_on || !$o->appointed_on->isFuture()));
        $past=$directors->filter(fn($o)=>$o->ceased_on && !$o->ceased_on->isFuture());
        $tenures=$current->filter(fn($o)=>$o->appointed_on && !$o->appointed_on->isFuture())->map(fn($o)=>$o->appointed_on->diffInDays(now())/365.2425);
        $years=$company->shareholdings->filter(fn($h)=>preg_match('/^\d{4}-\d{2}$/',(string)$h->financial_year))->groupBy('financial_year')->sortKeysDesc();
        $ownership=$years->map(function($rows,$year) use($company) {
            $categories=['Promoter Holding'=>['promoters','promoter'], 'Public Holding'=>['public / other than promoters','public'],
                'Institutional Holding'=>['institutional','institutions','institutional investors'], 'FII Holding'=>['fii','fiis','foreign institutional investors','foreign portfolio investors'],
                'Mutual Fund Holding'=>['mutual funds','mutual fund'], 'Body Corporate'=>['bodies corporate','body corporate'], 'KMP / Directors'=>['kmp / directors','directors and key managerial personnel','directors and kmp']];
            $values=[];
            foreach($categories as $label=>$aliases) {
                $matched=$rows->filter(function($r) use($aliases) {
                    $parts=preg_split('/[>\x{203A}]/u',strtolower(trim($r->category)));
                    $name=trim(preg_replace('/^\([ivx]+\)\s*/','',end($parts)));
                    return in_array($name,$aliases,true);
                });
                // Ambiguous/overlapping rows are not summed into a misleading percentage.
                $v=$matched->count()===1 ? $matched->first()->percentage : null;
                $values[$label]=$v !== null && is_numeric($v) && $v>=0 && $v<=100 ? (float)$v : null;
            }
            $statement=$company->financialStatements->where('is_consolidated',false)->firstWhere('financial_year',$year);
            return ['year'=>$year,'shares'=>$statement?->shares_outstanding,'values'=>$values];
        });
        return ['current'=>$current,'past'=>$past,'average_tenure'=>$tenures->isNotEmpty()?round($tenures->avg(),1):null,'tenure_count'=>$tenures->count(),
            'ownership'=>$ownership,'latest_ownership'=>$ownership->first(),
            'latest_filing'=>$company->filings->where('status','parsed')->sortByDesc('financial_year')->first()];
    }
}
