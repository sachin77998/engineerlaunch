<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

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

            $owner = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => config('owner.name'),
                    'password' => '$2y$10$3zKI9v8rYA54rh40X0BefeVYMoANbBU5T82czSt9Q0bTmgtKp/6fC',
                    'role' => 'admin',
                    'role_code' => 2,
                    'email_verified_at' => now(),
                ]
            );

            $owner->ownerProfile()->firstOrCreate([]);

            DB::table('owner_profiles')->where('user_id', '<>', $owner->id)->delete();
        });
    }
}
