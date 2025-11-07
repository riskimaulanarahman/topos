<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class Employee extends Authenticatable
{
    use HasApiTokens, HasFactory;

    protected $fillable = [
        'name','email','phone','pin','role','is_active'
    ];

    protected $hidden = [
        'pin',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }
}

