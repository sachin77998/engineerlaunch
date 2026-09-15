<aside class="learning-sidebar topic-sidebar" aria-label="{{$track['title']}} topics">
    <div class="sidebar-title"><span class="sidebar-kicker">CURRENT COURSE</span><h2>{{$track['title']}} topics</h2></div>
    <nav class="sidebar-links">
        @foreach($track['topics'] as $topicSlug => $topic)
            <a href="{{route('learning.show',[$trackSlug,$topicSlug])}}" class="{{$topicSlug === $moduleSlug ? 'active' : ''}}" @if($topicSlug === $moduleSlug) aria-current="page" @endif>
                <span>{{str_pad($loop->iteration,2,'0',STR_PAD_LEFT)}}</span>
                <b>{{$topic['title']}}</b>
                <small>{{count($topic['questions'])}} questions</small>
            </a>
        @endforeach
    </nav>
</aside>

<aside class="learning-sidebar course-sidebar" aria-label="Other courses">
    <div class="sidebar-title"><span class="sidebar-kicker">EXPLORE MORE</span><h2>Other courses</h2></div>
    <nav class="other-course-links">
        @foreach($allTracks as $otherSlug => $otherTrack)
            @continue($otherSlug === $trackSlug)
            <a href="{{route('learning.track',$otherSlug)}}" style="--course-color:{{$otherTrack['color']}}">
                <span class="course-icon">{{$otherTrack['icon']}}</span>
                <span><b>{{$otherTrack['title']}}</b><small>{{count($otherTrack['topics'])}} topics</small></span>
                <i>→</i>
            </a>
        @endforeach
    </nav>
    <a class="all-courses-link" href="{{route('learning.index')}}">View all courses →</a>
</aside>
