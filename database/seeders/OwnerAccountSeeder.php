<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class OwnerAccountSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $email = strtolower((string) config('owner.email'));

            User::query()
                ->where(fn ($query) => $query->where('role_code', 2)->orWhere('role', 'admin'))
                ->whereRaw('LOWER(email) <> ?', [$email])
                ->update(['role' => 'student', 'role_code' => 1]);

            $owner = User::query()->firstOrNew(['email' => $email]);
            if (! $owner->exists) {
                // Provision without a shared password; use owner:reset-password to set it.
                $owner->password = Hash::make(Str::random(64));
            }
            $owner->fill([
                    'name' => config('owner.name'),
                    'role' => 'admin',
                    'role_code' => 2,
                    'email_verified_at' => now(),
            ]);
            $owner->save();

            $owner->ownerProfile()->firstOrCreate([]);

            DB::table('owner_profiles')->where('user_id', '<>', $owner->id)->delete();
        });
    }
}
