<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'clock_in_at','clock_in_lat','clock_in_lng','clock_in_photo_path',
        'clock_out_at','clock_out_lat','clock_out_lng','clock_out_photo_path',
        'notes','work_minutes'
    ];

    protected $casts = [
        'clock_in_at' => 'datetime',
        'clock_out_at' => 'datetime',
        'work_minutes' => 'integer',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }
}

