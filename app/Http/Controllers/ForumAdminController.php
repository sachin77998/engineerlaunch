<?php

namespace App\Http\Controllers;

use App\Models\Forum\ForumAnswer;
use App\Models\Forum\ForumQuestion;
use App\Models\Forum\ForumReport;
use Illuminate\Http\Request;

// Forum moderation: open reports grouped by item, hide / show questions and answers, dismiss reports.
class ForumAdminController extends Controller
{
    public function index()
    {
        $open = ForumReport::with('user:id,name')->whereNull('resolved_at')->latest()->get()->groupBy(fn ($r) => $r->target_type . ':' . $r->target_id);
        $items = $open->map(fn ($reports) => (object) ['type' => $reports->first()->target_type, 'target' => $reports->first()->target(), 'reports' => $reports])->filter(fn ($i) => $i->target)->values();
        return view('forum.admin', [
            'items' => $items,
            'hidden' => ForumQuestion::where('is_hidden', true)->latest('updated_at')->take(20)->get(),
            'hiddenAnswers' => ForumAnswer::with('question')->where('is_hidden', true)->latest('updated_at')->take(20)->get(),
            'stats' => ['questions' => ForumQuestion::count(), 'answers' => ForumAnswer::count(), 'unanswered' => ForumQuestion::visible()->where('answers_count', 0)->count()],
        ]);
    }

    /** action: hide | show | dismiss (dismiss closes the reports and leaves the item visible). */
    public function moderate(Request $request, string $type, int $id)
    {
        abort_unless(in_array($type, ['question', 'answer'], true), 404);
        $action = $request->validate(['action' => 'required|in:hide,show,dismiss'])['action'];
        $target = $type === 'question' ? ForumQuestion::findOrFail($id) : ForumAnswer::findOrFail($id);
        if ($action !== 'dismiss') $target->update(['is_hidden' => $action === 'hide']);
        if ($action === 'dismiss') $target->update(['is_hidden' => false]);
        if ($type === 'answer') $target->question->recountAnswers();
        ForumReport::where('target_type', $type)->where('target_id', $id)->whereNull('resolved_at')->update(['resolved_at' => now()]);
        return back()->with('success', ucfirst($type) . ' ' . ['hide' => 'hidden', 'show' => 'visible again', 'dismiss' => 'kept; reports dismissed'][$action] . '.');
    }
}
