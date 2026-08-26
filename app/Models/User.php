<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'role_code',
        'total_points','correct_answers','total_stars','is_premium','premium_started_at','premium_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role_code' => 'integer',
        'is_premium' => 'boolean','premium_started_at'=>'datetime','premium_expires_at'=>'datetime',
    ];

    public function employerProfile(){return $this->hasOne(EmployerProfile::class);}
    public function postedJobs(){return $this->hasMany(Job::class,'employer_id');}
    public function candidateProfile(){return $this->hasOne(CandidateProfile::class);}
    public function ownerProfile(){return $this->hasOne(OwnerProfile::class);}
    public function applications(){return $this->hasMany(JobApplication::class);}
}
