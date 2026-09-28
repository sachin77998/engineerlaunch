<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\OwnerAccountSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OwnerPasswordTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('student');
            $table->unsignedTinyInteger('role_code')->default(1);
        });
        (require database_path('migrations/2026_08_24_000017_create_owner_profiles_table.php'))->up();
    }

    protected function tearDown(): void
    {
        DB::disconnect('sqlite');
        parent::tearDown();
    }

    public function test_repeated_deploy_seeding_preserves_existing_password(): void
    {
        $owner = User::create([
            'name' => 'Owner', 'email' => config('owner.email'),
            'password' => Hash::make('Existing-test-password'),
            'role' => 'admin', 'role_code' => 2,
        ]);
        $hash = $owner->password;
        $this->seed(OwnerAccountSeeder::class);
        $this->seed(OwnerAccountSeeder::class);
        $this->assertSame($hash, $owner->fresh()->password);
        $this->assertSame(1, $owner->ownerProfile()->count());
    }

    public function test_fresh_owner_can_be_reset_and_log_in_after_redeployment(): void
    {
        $this->seed(OwnerAccountSeeder::class);
        $owner = User::where('email', config('owner.email'))->firstOrFail();
        $oldHash = $owner->password;
        $owner->forceFill(['remember_token' => 'old-token'])->save();

        $this->artisan('owner:reset-password')
            ->expectsQuestion('New owner password', 'New-test-password$')
            ->expectsQuestion('Confirm new owner password', 'New-test-password$')
            ->assertSuccessful();

        $this->seed(OwnerAccountSeeder::class);
        $this->assertNotSame($oldHash, $owner->fresh()->password);
        $this->assertNotSame('old-token', $owner->fresh()->remember_token);
        $this->assertTrue(Hash::check('New-test-password$', $owner->fresh()->password));
        $this->post('/login', [
            'email' => config('owner.email'), 'password' => 'New-test-password$',
            'expected_role' => 'owner',
        ])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($owner);
    }

    public function test_mismatched_confirmation_does_not_change_password(): void
    {
        $this->seed(OwnerAccountSeeder::class);
        $owner = User::where('email', config('owner.email'))->firstOrFail();
        $hash = $owner->password;
        $this->artisan('owner:reset-password')
            ->expectsQuestion('New owner password', 'New-test-password$')
            ->expectsQuestion('Confirm new owner password', 'Different-password')
            ->assertFailed();
        $this->assertSame($hash, $owner->fresh()->password);
    }

    public function test_short_password_is_rejected(): void
    {
        $this->seed(OwnerAccountSeeder::class);
        $owner = User::where('email', config('owner.email'))->firstOrFail();
        $hash = $owner->password;
        $this->artisan('owner:reset-password')
            ->expectsQuestion('New owner password', 'short')
            ->assertFailed();
        $this->assertSame($hash, $owner->fresh()->password);
    }
}
