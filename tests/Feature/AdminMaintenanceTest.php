<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminMaintenanceTest extends TestCase
{
    use DatabaseTransactions;

    private function owner(): User
    {
        return User::where('email', config('owner.email'))->first()
            ?? User::forceCreate(['name' => 'Owner', 'email' => config('owner.email'), 'password' => Hash::make('secret-pass'), 'role' => 'admin', 'role_code' => 2]);
    }

    public function test_owner_can_open_setup_page_and_run_a_whitelisted_step(): void
    {
        $this->actingAs($this->owner())->get('/admin/maintenance')->assertOk()->assertSee('Production Setup')->assertSee('php artisan migrate');
        $this->postJson('/admin/maintenance/run/status')->assertOk()->assertJson(['step' => 'status', 'exit' => 0]);
        $this->postJson('/admin/maintenance/run/not-a-step')->assertNotFound();
    }

    public function test_non_owner_cannot_run_production_commands(): void
    {
        $user = User::forceCreate(['name' => 'HR', 'email' => 'hr-maint@example.test', 'password' => Hash::make('secret-pass'), 'role' => 'employer', 'role_code' => 0]);
        $this->actingAs($user)->get('/admin/maintenance')->assertForbidden();
        $this->postJson('/admin/maintenance/run/migrate')->assertForbidden();
    }
}
