<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

use App\Models\Traits\BelongsToOutlet;

class Expense extends Model
{
    use HasFactory, \App\Models\Traits\Blameable, BelongsToOutlet;

    protected $fillable = [
        'date',
        'reference_no',
        'amount',
        'category_id',
        'vendor',
        'notes',
        'attachment_path',
        'created_by',
        'updated_by',
        'outlet_id',
    ];

    protected $casts = [
        'date' => 'date',
        'amount' => 'decimal:2',
    ];

    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(ExpenseItem::class);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }
}
