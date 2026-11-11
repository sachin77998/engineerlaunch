<?php

namespace App\Http\Controllers;

use App\Models\Interior\InteriorIdea;
use App\Models\Interior\InteriorLead;
use App\Models\Interior\InteriorProject;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// Owner's desk for interiors: quote requests, the design idea gallery (photo uploads) and completed projects.
class InteriorAdminController extends Controller
{
    public function index(Request $request)
    {
        $status = array_key_exists($request->query('status'), config('interiors.statuses')) ? $request->query('status') : null;
        return view('interiors.admin', [
            'leads' => InteriorLead::with('idea')->when($status, fn ($q) => $q->where('status', $status))->latest()->paginate(30)->withQueryString(),
            'status' => $status, 'counts' => InteriorLead::selectRaw('status, count(*) n')->groupBy('status')->pluck('n', 'status'),
            'ideas' => InteriorIdea::latest()->take(60)->get(), 'projects' => InteriorProject::latest()->get(),
        ]);
    }

    public function updateLead(Request $request, InteriorLead $lead)
    {
        $lead->update($request->validate(['status' => 'required|in:' . implode(',', array_keys(config('interiors.statuses'))), 'admin_note' => 'nullable|string|max:2000']));
        return back()->with('success', 'Lead updated.');
    }

    /** Upload one or more photos as ideas sharing the same room / style / category. */
    public function storeIdeas(Request $request)
    {
        $c = config('interiors');
        $v = $request->validate([
            'title' => 'required|string|max:120', 'room' => 'nullable|in:' . implode(',', array_keys($c['rooms'])), 'style' => 'nullable|in:' . implode(',', array_keys($c['styles'])),
            'category' => 'nullable|in:' . implode(',', array_keys($c['categories'])), 'description' => 'nullable|string|max:600', 'is_trending' => 'nullable|boolean',
            'images' => 'required|array|max:20', 'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:' . $c['image_max_kb'],
        ]);
        foreach ($v['images'] as $i => $img) {
            $title = count($v['images']) > 1 ? $v['title'] . ' ' . ($i + 1) : $v['title'];
            InteriorIdea::create([
                'slug' => Str::slug($title) . '-' . Str::lower(Str::random(5)), 'title' => $title, 'room' => $v['room'] ?? null, 'style' => $v['style'] ?? null,
                'category' => $v['category'] ?? null, 'description' => $v['description'] ?? null, 'is_trending' => $request->boolean('is_trending'),
                'image_path' => $img->store('interiors/ideas', 'public'),
            ]);
        }
        return back()->with('success', count($v['images']) . ' design ' . (count($v['images']) === 1 ? 'idea' : 'ideas') . ' added.');
    }

    public function updateIdea(Request $request, InteriorIdea $idea)
    {
        if ($request->input('action') === 'delete') {
            Storage::disk('public')->delete($idea->image_path);
            $idea->delete();
            return back()->with('success', 'Idea deleted.');
        }
        $idea->update(['is_trending' => $request->boolean('is_trending'), 'is_active' => $request->boolean('is_active')]);
        return back()->with('success', 'Idea saved.');
    }

    public function storeProject(Request $request, ?InteriorProject $project = null)
    {
        $c = config('interiors');
        $editing = $project && $project->exists;
        $v = $request->validate([
            'title' => 'required|string|max:120', 'city' => 'required|string|max:80', 'style' => 'nullable|in:' . implode(',', array_keys($c['styles'])),
            'bhk' => 'nullable|integer|min:1|max:10', 'rating' => 'nullable|integer|min:1|max:5', 'video_url' => 'nullable|url|max:255',
            'review' => 'nullable|string|max:3000', 'reviewer' => 'nullable|string|max:120',
            'cover' => ($editing ? 'nullable' : 'required') . '|image|mimes:jpg,jpeg,png,webp|max:' . $c['image_max_kb'],
            'photos' => 'nullable|array|max:30', 'photos.*' => 'image|mimes:jpg,jpeg,png,webp|max:' . $c['image_max_kb'],
        ]);
        $data = collect($v)->except(['cover', 'photos'])->all() + ['is_featured' => $request->boolean('is_featured'), 'is_active' => $request->boolean('is_active', true)];
        if ($request->hasFile('cover')) {
            if ($editing) Storage::disk('public')->delete($project->cover_path);
            $data['cover_path'] = $request->file('cover')->store('interiors/projects', 'public');
        }
        $new = array_map(fn ($f) => $f->store('interiors/projects', 'public'), $request->file('photos', []));
        if ($editing) {
            $project->update($data + ['photos' => array_merge($project->photos ?? [], $new)]);
        } else {
            $project = InteriorProject::create($data + ['slug' => Str::slug($v['title'] . ' ' . $v['city']) . '-' . Str::lower(Str::random(4)), 'photos' => $new]);
        }
        if ($project->is_featured) InteriorProject::where('id', '!=', $project->id)->update(['is_featured' => false]);
        return back()->with('success', 'Project saved.');
    }

    public function destroyProject(InteriorProject $project)
    {
        Storage::disk('public')->delete(array_merge([$project->cover_path], $project->photos ?? []));
        $project->delete();
        return back()->with('success', 'Project deleted.');
    }
}
