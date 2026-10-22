<?php

namespace App\Http\Controllers;

use App\Mail\HiringMail;
use App\Models\EmployerProfile;
use App\Models\HiringTeamMember;
use App\Models\User;
use App\Services\Hiring\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class HiringTeamController extends Controller
{
    public function __construct(private Team $team) {}

    public function index(Request $r)
    {
        $owner = User::find($this->team->ownerId($r->user()));
        $members = HiringTeamMember::with('member:id,name,email')->where('owner_id', $owner->id)->where('status', '!=', 'removed')->orderBy('status')->orderBy('email')->get();
        $activity = DB::table('jobs')->whereIn('employer_id', $this->team->userIds($r->user()))->select('employer_id', DB::raw('count(*) jobs'))->groupBy('employer_id')->pluck('jobs', 'employer_id');
        return view('employer.hiring.team', ['owner' => $owner, 'members' => $members, 'activity' => $activity, 'myRole' => $this->team->role($r->user()), 'canManage' => $this->team->can($r->user(), 'team')]);
    }

    public function invite(Request $r)
    {
        $this->team->authorize($r->user(), 'team');
        $d = $r->validate(['email' => 'required|email:rfc|max:190', 'role' => 'required|in:' . implode(',', array_keys(Team::ROLES))]);
        $email = Str::lower(trim($d['email']));
        $ownerId = $this->team->ownerId($r->user());
        $owner = User::with('employerProfile')->find($ownerId);
        abort_unless($owner->employerProfile?->company_id, 422, 'Complete the company profile before inviting your team.');
        abort_if(Str::lower($owner->email) === $email || Str::lower($r->user()->email) === $email, 422, 'You are already on this team.');
        if ($existing = User::whereRaw('LOWER(email) = ?', [$email])->first()) {
            abort_if(!in_array($existing->role, ['employer', null], true) && !in_array($existing->role_code, [0], true), 422, 'That email belongs to a candidate or admin account. Ask them to use their work email.');
            abort_if(HiringTeamMember::where('member_id', $existing->id)->where('status', 'active')->where('owner_id', '!=', $ownerId)->exists(), 422, 'That person is already on another company\'s hiring team.');
            abort_if(HiringTeamMember::where('owner_id', $existing->id)->where('status', 'active')->exists(), 422, 'That person already runs their own hiring team.');
        }
        $token = Str::random(48);
        $member = HiringTeamMember::updateOrCreate(['owner_id' => $ownerId, 'email' => $email], ['invited_by' => $r->user()->id, 'role' => $d['role'], 'status' => 'pending',
            'token_hash' => hash('sha256', $token), 'invite_expires_at' => now()->addDays(7), 'member_id' => null, 'accepted_at' => null]);
        $this->mailInvite($member, $token, $r->user(), $owner->employerProfile->company->name ?? 'our company');
        return back()->with('success', 'Invite emailed to ' . $email . '. The link works for 7 days.');
    }

    public function resend(Request $r, HiringTeamMember $member)
    {
        $this->own($r, $member);
        abort_unless($member->status === 'pending', 422);
        $token = Str::random(48);
        $member->update(['token_hash' => hash('sha256', $token), 'invite_expires_at' => now()->addDays(7)]);
        $this->mailInvite($member, $token, $r->user(), User::with('employerProfile.company')->find($member->owner_id)->employerProfile?->company?->name ?? 'our company');
        return back()->with('success', 'Invite re-sent.');
    }

    public function update(Request $r, HiringTeamMember $member)
    {
        $this->own($r, $member);
        abort_if($member->member_id === $r->user()->id, 422, 'You cannot change your own role.');
        $member->update($r->validate(['role' => 'required|in:' . implode(',', array_keys(Team::ROLES))]));
        return back()->with('success', 'Role updated.');
    }

    public function remove(Request $r, HiringTeamMember $member)
    {
        $this->own($r, $member);
        abort_if($member->member_id === $r->user()->id, 422, 'Use "Leave team" to remove yourself.');
        // Removed members keep their login but lose access; jobs they posted stay with the team owner.
        DB::transaction(function () use ($member) {
            if ($member->member_id) DB::table('jobs')->where('employer_id', $member->member_id)->update(['employer_id' => $member->owner_id]);
            $member->update(['status' => 'removed', 'token_hash' => null]);
        });
        return back()->with('success', $member->email . ' removed from the team.');
    }

    public function leave(Request $r)
    {
        $m = $this->team->membership($r->user());
        abort_unless($m, 422, 'You are the owner of this team.');
        DB::transaction(function () use ($m) {
            DB::table('jobs')->where('employer_id', $m->member_id)->update(['employer_id' => $m->owner_id]);
            $m->update(['status' => 'removed']);
        });
        return redirect()->route('employer.dashboard')->with('success', 'You left the team.');
    }

    // Public: the link from the invite email.
    public function show(Request $r, string $token)
    {
        $invite = $this->invite_($token);
        $company = User::with('employerProfile.company')->find($invite->owner_id)?->employerProfile?->company?->name;
        if ($r->user()) {
            if (Str::lower($r->user()->email) !== $invite->email) return view('employer.hiring.invite', ['invite' => $invite, 'company' => $company, 'mismatch' => true]);
            return $this->accept($r->user(), $invite);
        }
        if (User::whereRaw('LOWER(email) = ?', [$invite->email])->exists()) {
            session()->put('url.intended', $r->fullUrl());
            return redirect()->route('employer.login')->with('success', 'Sign in as ' . $invite->email . ' to join ' . $company . '.');
        }
        return view('employer.hiring.invite', ['invite' => $invite, 'company' => $company, 'mismatch' => false, 'token' => $token]);
    }

    // Public: a new team member creates their account from the invite.
    public function register(Request $r, string $token)
    {
        $invite = $this->invite_($token);
        abort_if(User::whereRaw('LOWER(email) = ?', [$invite->email])->exists(), 422, 'An account with this email already exists; sign in instead.');
        $d = $r->validate(['name' => 'required|string|max:120', 'designation' => 'nullable|string|max:100', 'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()]]);
        $user = DB::transaction(function () use ($d, $invite) {
            $user = User::forceCreate(['name' => $d['name'], 'email' => $invite->email, 'password' => Hash::make($d['password']), 'role' => 'employer', 'role_code' => 0, 'email_verified_at' => now()]);
            EmployerProfile::create(['user_id' => $user->id, 'company_id' => EmployerProfile::where('user_id', $invite->owner_id)->value('company_id'), 'designation' => $d['designation'] ?? null, 'first_name' => strtok($d['name'], ' ')]);
            return $user;
        });
        Auth::login($user);
        $r->session()->regenerate();
        return $this->accept($user, $invite);
    }

    private function accept(User $user, HiringTeamMember $invite)
    {
        abort_if(HiringTeamMember::where('member_id', $user->id)->where('status', 'active')->where('owner_id', '!=', $invite->owner_id)->exists(), 422, 'You are already on another company\'s hiring team. Leave it first.');
        abort_if(HiringTeamMember::where('owner_id', $user->id)->where('status', 'active')->exists(), 422, 'You run your own hiring team, so you cannot join another one.');
        DB::transaction(function () use ($user, $invite) {
            $invite->update(['member_id' => $user->id, 'status' => 'active', 'accepted_at' => now(), 'token_hash' => null]);
            $companyId = EmployerProfile::where('user_id', $invite->owner_id)->value('company_id');
            EmployerProfile::updateOrCreate(['user_id' => $user->id], ['company_id' => $companyId]);
            if ($user->role !== 'employer') $user->forceFill(['role' => 'employer', 'role_code' => 0])->save();
        });
        $this->team->forget();
        return redirect()->route('employer.hiring.talent')->with('success', 'Welcome to the team. You now see the team\'s jobs and candidates.');
    }

    private function invite_(string $token): HiringTeamMember
    {
        $invite = HiringTeamMember::where('token_hash', hash('sha256', $token))->where('status', 'pending')->first();
        abort_unless($invite && $invite->invite_expires_at?->isFuture(), 404, 'This invite link is invalid or has expired. Ask your admin to resend it.');
        return $invite;
    }

    private function own(Request $r, HiringTeamMember $member): void
    {
        $this->team->authorize($r->user(), 'team');
        abort_unless((int) $member->owner_id === $this->team->ownerId($r->user()), 403);
    }

    private function mailInvite(HiringTeamMember $member, string $token, User $by, string $company): void
    {
        $link = route('hiring.invite.show', $token);
        $body = "Hello,\n\n{$by->name} has invited you to join the {$company} hiring team on " . config('app.name') . ' as ' . Team::ROLES[$member->role] . ".\n\nAccept the invite: {$link}\n\nThe link expires in 7 days. If you were not expecting this, ignore this email.";
        Mail::to($member->email)->queue((new HiringMail('Join the ' . $company . ' hiring team', $body, $by->email))->onQueue('emails'));
    }
}
