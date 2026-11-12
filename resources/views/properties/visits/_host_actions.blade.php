{{-- Host buttons for one visit. Expects $v and $action (form URL). --}}
@if($v->isOpen())
    <form method="post" action="{{ $action }}" style="display:flex;gap:6px;flex-wrap:wrap;align-items:center">@csrf
        <input name="host_note" maxlength="500" placeholder="Note to visitor (optional)" value="{{ $v->host_note }}" style="flex:1;min-width:180px" aria-label="Note to visitor">
        @if($v->visit_at->isFuture())
            @if($v->status === 'requested')<button class="pr-btn sm" name="action" value="confirm">Confirm</button>@endif
        @else
            <button class="pr-btn sm" name="action" value="complete">Visited</button>
            <button class="pr-btn ghost sm" name="action" value="no_show">Didn't come</button>
        @endif
        <button class="pr-btn grey sm" name="action" value="cancel" onclick="return confirm('Cancel this visit?')">Cancel</button>
    </form>
@else
    <span class="pr-tag">{{ $v->statusLabel() }}</span>
@endif
