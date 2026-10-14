<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class GrantPremium extends Command
{
    protected $signature = 'users:premium {email} {--months=12 : Length of the plan} {--revoke : End the plan now}';
    protected $description = 'Give (or remove) a premium plan for a user, e.g. after an offline payment';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();
        if (!$user) { $this->error('No user with that email.'); return self::FAILURE; }
        if ($this->option('revoke')) {
            $user->forceFill(['is_premium' => false, 'premium_expires_at' => now()])->save();
            $this->info("Premium ended for {$user->email}.");
            return self::SUCCESS;
        }
        $from = $user->premium_expires_at && $user->premium_expires_at->isFuture() ? $user->premium_expires_at : now();
        $user->forceFill(['is_premium' => true, 'premium_started_at' => $user->premium_started_at ?? now(),
            'premium_expires_at' => $from->copy()->addMonths(max(1, (int) $this->option('months')))])->save();
        $this->info("Premium active for {$user->email} until {$user->premium_expires_at->format('d M Y')}.");
        return self::SUCCESS;
    }
}
