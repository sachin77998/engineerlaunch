<?php

namespace App\Services\Hiring;

use App\Models\HiringTeamMember;
use App\Models\User;

// Resolves a recruiter's hiring team. Everyone on a team sees the team's jobs and candidates;
// what they may change depends on their role. Without an accepted invite a user is the owner of a team of one.
class Team
{
    public const ROLES = ['admin' => 'Admin', 'recruiter' => 'Recruiter', 'interviewer' => 'Interviewer', 'viewer' => 'Viewer'];
    // Which abilities each role has; the owner has all of them.
    public const ABILITIES = [
        'admin' => ['view', 'edit', 'email', 'offer', 'verify', 'team', 'costs'],
        'recruiter' => ['view', 'edit', 'email', 'offer', 'verify'],
        'interviewer' => ['view', 'feedback'],
        'viewer' => ['view'],
    ];

    private array $cache = [];

    public function membership(User $user): ?HiringTeamMember
    {
        return $this->resolve($user)['membership'];
    }

    public function ownerId(User $user): int
    {
        return $this->resolve($user)['owner'];
    }

    public function role(User $user): string
    {
        return $this->resolve($user)['role'];
    }

    // Users whose jobs this user may see: the owner plus every active member.
    public function userIds(User $user): array
    {
        return $this->resolve($user)['ids'];
    }

    public function can(User $user, string $ability): bool
    {
        if ($user->role === 'admin') return true;
        $role = $this->role($user);
        return $role === 'owner' || in_array($ability, self::ABILITIES[$role] ?? [], true) || ($ability === 'feedback' && in_array('edit', self::ABILITIES[$role] ?? [], true));
    }

    public function authorize(User $user, string $ability): void
    {
        abort_unless($this->can($user, $ability), 403, 'Your team role does not allow this action.');
    }

    public function forget(?User $user = null): void
    {
        $user ? (function () use ($user) { unset($this->cache[$user->id]); })() : $this->cache = [];
    }

    private function resolve(User $user): array
    {
        if (isset($this->cache[$user->id])) return $this->cache[$user->id];
        $m = HiringTeamMember::where('member_id', $user->id)->where('status', 'active')->first();
        $owner = $m ? (int) $m->owner_id : (int) $user->id;
        $ids = HiringTeamMember::where('owner_id', $owner)->where('status', 'active')->whereNotNull('member_id')->pluck('member_id')->map(fn ($id) => (int) $id)->push($owner)->unique()->values()->all();
        return $this->cache[$user->id] = ['membership' => $m, 'owner' => $owner, 'role' => $m ? $m->role : 'owner', 'ids' => $ids];
    }
}
