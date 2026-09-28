<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ResetOwnerPassword extends Command
{
    protected $signature = 'owner:reset-password';

    protected $description = 'Reset the configured owner password using hidden terminal prompts';

    public function handle(): int
    {
        $owner = User::where('email', strtolower((string) config('owner.email')))
            ->where(fn ($query) => $query->where('role_code', 2)->orWhere('role', 'admin'))
            ->first();

        if (! $owner) {
            $this->error('Owner account not found. Run the OwnerAccountSeeder first.');
            return self::FAILURE;
        }

        $password = $this->secret('New owner password');
        if (! is_string($password) || strlen($password) < 8) {
            $this->error('The password must contain at least 8 characters.');
            return self::FAILURE;
        }

        if ($password !== $this->secret('Confirm new owner password')) {
            $this->error('Passwords do not match. Nothing was changed.');
            return self::FAILURE;
        }

        $owner->forceFill([
            'password' => Hash::make($password),
            'remember_token' => Str::random(60),
        ])->save();

        $this->info('Owner password updated. Sign in at /owner/login.');
        return self::SUCCESS;
    }
}
