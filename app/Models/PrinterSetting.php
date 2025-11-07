<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PrinterSetting extends Model
{
    use HasFactory;

    protected $table = 'outlet_printer_settings';

    protected $fillable = [
        'outlet_id',
        'paper_size',
        'title_font_size',
        'show_logo',
        'logo_path',
        'show_footer',
        'footer_text',
    ];

    protected $casts = [
        'show_logo' => 'bool',
        'show_footer' => 'bool',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}
