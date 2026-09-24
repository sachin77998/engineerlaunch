<?php
namespace App\Services;

use Illuminate\Http\Request;

class SiteExplanation
{
    public function forRequest(Request $request): ?array
    {
        $name=(string)$request->route()?->getName();
        // Lessons already have topic-specific visual overviews and answer blocks.
        if ($request->routeIs('learning.*') || $request->is('api/*')) return null;
        $groups=[
            'industrial'=>['industrial.*'], 'experiences'=>['company.experiences.*'],
            'companies'=>['companies.*'], 'news'=>['news.*'], 'resume'=>['resume.*'],
            'practice'=>['practice'], 'about'=>['about'], 'contact'=>['contact'],
            'account'=>['login*','register*','otp.*','student.*','employer.login','employer.register*'],
            'profile'=>['dashboard','candidate.*'], 'employer'=>['employer.*'], 'admin'=>['admin.*'],
            'jobs'=>['home','jobs.*','opportunities.*','applications.*'],
        ];
        foreach($groups as $key=>$patterns) if ($request->routeIs(...$patterns)) return ['key'=>$key]+config('site_explanations.'.$key,[]);
        return null;
    }
}
