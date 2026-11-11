<?php

namespace App\Models\Interior;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class InteriorProject extends Model
{
    protected $guarded = [];

    protected $casts = ['photos' => 'array', 'is_featured' => 'boolean', 'is_active' => 'boolean'];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function coverUrl(): string
    {
        return Storage::disk('public')->url($this->cover_path);
    }

    public function photoUrls(): array
    {
        return array_map(fn ($p) => Storage::disk('public')->url($p), $this->photos ?? []);
    }

    public function styleLabel(): ?string { return config('interiors.styles')[$this->style][0] ?? null; }

    /** YouTube / Vimeo embed URL for the video tour, or null for other links. */
    public function videoEmbed(): ?string
    {
        $u = (string) $this->video_url;
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|shorts/|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})~', $u, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        if (preg_match('~vimeo\.com/(\d+)~', $u, $m)) return 'https://player.vimeo.com/video/' . $m[1];
        return null;
    }
}
