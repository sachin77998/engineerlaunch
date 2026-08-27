<section class="panel" style="margin-bottom:22px;border-color:#bfdbfe;background:linear-gradient(135deg,#eff6ff,#fff)">
 <h2 style="margin-top:0">Resume skill matching</h2>
 <p>After your PDF is parsed, Ascendia compares extracted skills with HR-posted portal jobs. For example, HTML, CSS and JavaScript are matched against required and mandatory job skills.</p>
 <form method="post" action="{{route('candidate.auto-apply.update')}}">@csrf
  <label class="choice" style="margin:12px 0"><input type="checkbox" name="enabled" value="1" @checked($autoApply?->enabled)> <span><strong>Allow automatic internal applications</strong><br><small>Submit only to matching jobs posted by HR on Ascendia. External career sites, jobs with required screening questions, and duplicate applications are excluded.</small></span></label>
  <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
   <div><label class="label" for="minimum_match_score">Minimum match score</label><select class="field" id="minimum_match_score" name="minimum_match_score">@foreach([60,70,75,80,90] as $score)<option value="{{$score}}" @selected(($autoApply?->minimum_match_score??75)===$score)>{{$score}}% or higher</option>@endforeach</select></div>
   <div><label class="label" for="daily_limit">Daily application limit</label><select class="field" id="daily_limit" name="daily_limit">@foreach(range(1,10) as $limit)<option value="{{$limit}}" @selected(($autoApply?->daily_limit??10)===$limit)>{{$limit}} {{$limit===1?'job':'jobs'}}</option>@endforeach</select></div>
  </div>
  <button class="btn btn-primary" style="margin-top:14px">Save matching preference</button>
 </form>
 <p style="margin-bottom:0;color:#64748b;font-size:13px">Every automatic application stores its score, matched skills, resume ID and application source for audit and withdrawal.</p>
</section>
