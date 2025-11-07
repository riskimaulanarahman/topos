<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Blameable;

class Unit extends Model
{
    use HasFactory, Blameable;

    protected $fillable = [
        'code',
        'name',
        'description',
        'created_by',
        'updated_by',
    ];
}
