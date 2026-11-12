<?php

namespace App\Models\Property;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** A booked site visit to a listing or a project. */
class SiteVisit extends Model
{
    public const OPEN = ['requested', 'confirmed'];

    protected $guarded = ['id'];
    protected $casts = ['visit_at' => 'datetime', 'confirmed_at' => 'datetime'];

    public function visitable() { return $this->morphTo(); }

    public function user() { return $this->belongsTo(User::class); }

    public function scopeOpen(Builder $q): Builder { return $q->whereIn('status', self::OPEN); }

    public function scopeUpcoming(Builder $q): Builder { return $q->open()->where('visit_at', '>=', now()); }

    public function isOpen(): bool { return in_array($this->status, self::OPEN, true); }

    /** The visitor can still change or cancel it (open and not yet started). */
    public function changeable(): bool { return $this->isOpen() && $this->visit_at->isFuture(); }

    public function statusLabel(): string { return config('site_visits.statuses.' . $this->status, ucfirst($this->status)); }

    public function reference(): string { return 'SV' . str_pad((string) $this->id, 6, '0', STR_PAD_LEFT); }

    public function title(): string
    {
        $v = $this->visitable;
        return $v instanceof Project ? $v->name : ($v?->title ?? 'Removed listing');
    }

    public function url(): ?string
    {
        $v = $this->visitable;
        if (!$v) return null;
        return $v instanceof Project ? route('projects.show', $v) : route('properties.show', $v);
    }

    /** Where it is, for the visitor: address of a listing or project. */
    public function place(): string
    {
        $v = $this->visitable;
        if ($v instanceof Project) return collect([$v->address, $v->locality, $v->city])->filter()->unique()->implode(', ');
        return $v ? $v->address() : '';
    }
}
