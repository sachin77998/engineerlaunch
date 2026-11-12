<?php

namespace App\Models\Property;

use Illuminate\Database\Eloquent\Model;

/** A YouTube or Vimeo video of the project, embedded privacy-friendly. */
class ProjectVideo extends Model
{
    protected $guarded = ['id'];

    public function embedUrl(): ?string
    {
        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $this->url, $m)) return 'https://www.youtube-nocookie.com/embed/' . $m[1];
        if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $this->url, $m)) return 'https://player.vimeo.com/video/' . $m[1];
        return null;
    }

    public function thumbUrl(): ?string
    {
        return preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $this->url, $m) ? 'https://i.ytimg.com/vi/' . $m[1] . '/hqdefault.jpg' : null;
    }
}
