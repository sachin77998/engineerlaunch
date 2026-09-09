@if($mechanicalArticles->isNotEmpty())
<section class="mechanical-intelligence" aria-labelledby="mechanical-news-heading">
    <div class="mechanical-heading">
        <div>
            <span class="mechanical-eyebrow">MECHANICAL &amp; MANUFACTURING INTELLIGENCE</span>
            <h2 id="mechanical-news-heading">Latest in Mechanical Industry</h2>
            <p>Manufacturing plants, engineering investments, automobiles, tractors, forging, EVs, automation and the skills changing mechanical careers.</p>
        </div>
        <a class="mechanical-view-all" href="{{route('news.index',['category'=>'mechanical-engineering'])}}">View all mechanical news <span aria-hidden="true">→</span></a>
    </div>
    <div class="mechanical-news-grid">
        @foreach($mechanicalArticles as $article)
        <article class="mechanical-news-card">
            <div class="mechanical-card-top">
                <span>{{$article->category?->icon}} {{strtoupper($article->category?->name ?? 'Mechanical Industry')}}</span>
                <time datetime="{{optional($article->source_published_at ?? $article->published_at)->toDateString()}}">{{optional($article->source_published_at ?? $article->published_at)->format('d M Y')}}</time>
            </div>
            <h3><a href="{{route('news.show',$article->slug)}}">{{$article->title}}</a></h3>
            <p>{{$article->excerpt ?? $article->summary}}</p>
            <div class="mechanical-card-footer">
                <span>{{$article->company_name ?? $article->source?->name ?? 'Industry Intelligence'}}</span>
                <a href="{{route('news.show',$article->slug)}}">Read analysis <span aria-hidden="true">→</span></a>
            </div>
        </article>
        @endforeach
    </div>
</section>
<style>
.mechanical-intelligence{margin:54px 0 42px}.mechanical-heading{display:flex;align-items:flex-end;justify-content:space-between;gap:30px;margin-bottom:28px}.mechanical-eyebrow{display:inline-block;margin-bottom:10px;color:#1769e0;font-size:12px;font-weight:800;letter-spacing:1.3px}.mechanical-heading h2{margin:0 0 10px;color:#0b2748;font-size:clamp(30px,4vw,38px);line-height:1.15}.mechanical-heading p{max-width:720px;margin:0;color:#61758a;font-size:16px;line-height:1.7}.mechanical-view-all{flex-shrink:0;padding:11px 16px;border:1px solid #b9d4f4;border-radius:10px;background:#fff;color:#1769e0!important;text-decoration:none!important;font-weight:800;transition:.2s}.mechanical-view-all:hover{transform:translateY(-2px);box-shadow:0 9px 20px rgba(20,55,90,.1)}.mechanical-news-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:20px}.mechanical-news-card{min-height:310px;display:flex;flex-direction:column;padding:25px;border:1px solid #dce6f0!important;border-radius:18px;background:#fff;transition:transform .2s ease,box-shadow .2s ease,border-color .2s ease}.mechanical-news-card:hover{transform:translateY(-4px);border-color:#b9d4f4!important;box-shadow:0 15px 35px rgba(20,55,90,.1)}.mechanical-card-top{display:flex;justify-content:space-between;gap:12px;margin-bottom:18px;color:#1769e0;font-size:11px;font-weight:800;letter-spacing:.7px}.mechanical-card-top time{color:#8295a8;font-weight:600;white-space:nowrap}.mechanical-news-card h3{margin:0 0 14px;font-size:21px;line-height:1.4}.mechanical-news-card h3 a{color:#0b2748!important;text-decoration:none}.mechanical-news-card p{margin:0;color:#61758a;font-size:15px;line-height:1.7}.mechanical-card-footer{display:flex;align-items:center;justify-content:space-between;gap:15px;margin-top:auto;padding-top:22px;border-top:1px solid #edf2f7}.mechanical-card-footer>span{color:#71869a;font-size:13px}.mechanical-card-footer a{color:#1769e0!important;text-decoration:none!important;font-size:14px;font-weight:800;white-space:nowrap}@media(max-width:900px){.mechanical-news-grid{grid-template-columns:repeat(2,minmax(0,1fr))}.mechanical-heading{align-items:flex-start;flex-direction:column}}@media(max-width:600px){.mechanical-news-grid{grid-template-columns:1fr}.mechanical-heading h2{font-size:30px}.mechanical-card-footer{align-items:flex-start;flex-direction:column}}
</style>
@endif
