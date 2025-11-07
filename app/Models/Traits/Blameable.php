<?php

namespace App\Models\Traits;

use Illuminate\Support\Facades\Auth;

trait Blameable
{
    public static function bootBlameable(): void
    {
        static::creating(function ($model) {
            if ($model->isFillable('created_by') && ! $model->getAttribute('created_by')) {
                $model->setAttribute('created_by', Auth::id());
            }
            if ($model->isFillable('updated_by') && ! $model->getAttribute('updated_by')) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });

        static::updating(function ($model) {
            if ($model->isFillable('updated_by')) {
                $model->setAttribute('updated_by', Auth::id());
            }
        });
    }
}

