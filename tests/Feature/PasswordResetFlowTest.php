<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Tests\TestCase;
class PasswordResetFlowTest extends TestCase {
 protected function setUp():void {
  parent::setUp();config(['database.default'=>'sqlite','database.connections.sqlite.database'=>':memory:','cache.default'=>'array','mail.default'=>'array','app.url'=>'https://portal.example']);
  DB::purge('sqlite');
  (require database_path('migrations/2014_10_12_000000_create_users_table.php'))->up();
  Schema::table('users',function(Blueprint $t){$t->string('role')->default('student');$t->unsignedTinyInteger('role_code')->default(1);});
  (require database_path('migrations/2014_10_12_100000_create_password_resets_table.php'))->up();
 }
 protected function tearDown():void {DB::disconnect('sqlite');parent::tearDown();}
 private function user():User{return User::create(['name'=>'Test','email'=>'reset@example.test','password'=>Hash::make('old-password'),'remember_token'=>'old-session']);}
 public function test_reset_email_uses_trusted_url_and_unknown_accounts_have_same_response():void {
  Notification::fake();$u=$this->user();
  $this->from('/forgot-password')->post('/forgot-password',['email'=>$u->email])->assertRedirect('/forgot-password')->assertSessionHas('status');
  $message=session('status');
  Notification::assertSentTo($u,ResetPassword::class,function($notification)use($u){$mail=$notification->toMail($u);$this->assertStringStartsWith('https://portal.example/reset-password/',$mail->actionUrl);return true;});
  $this->from('/forgot-password')->post('/forgot-password',['email'=>'missing@example.test'])->assertSessionHas('status',$message);
 }
 public function test_token_changes_password_rotates_session_and_cannot_be_reused():void {
  $u=$this->user();$token=Password::createToken($u);$data=['email'=>$u->email,'token'=>$token,'password'=>'new-password123','password_confirmation'=>'new-password123'];
  $this->post('/reset-password',$data)->assertRedirect('/login');
  $this->assertTrue(Hash::check('new-password123',$u->fresh()->password));$this->assertNotSame('old-session',$u->fresh()->remember_token);
  $this->post('/reset-password',$data)->assertSessionHasErrors('email');
 }
 public function test_expired_token_does_not_change_password():void {
  $u=$this->user();$hash=$u->password;$token=Password::createToken($u);
  DB::table('password_resets')->update(['created_at'=>now()->subHours(2)]);
  $this->post('/reset-password',['email'=>$u->email,'token'=>$token,'password'=>'new-password123','password_confirmation'=>'new-password123'])->assertSessionHasErrors('email');
  $this->assertSame($hash,$u->fresh()->password);
 }
 public function test_reset_forms_render():void {
  $this->get('/forgot-password')->assertOk()->assertSee('email');
  $this->get('/reset-password/sample?email=reset@example.test')->assertOk()->assertSee('password_confirmation');
 }
}
