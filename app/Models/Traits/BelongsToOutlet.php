<?php

namespace App\Models\Traits;

use App\Scopes\OutletScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

trait BelongsToOutlet
{
    protected static array $tableHasUserColumnCache = [];

    public static function bootBelongsToOutlet(): void
    {
        static::addGlobalScope(new OutletScope());

        static::creating(function ($model) {
            if (! $model->outlet_id && ($outletId = OutletScope::getActiveOutletId())) {
                $model->outlet_id = $outletId;
            }

            if (
                static::tableHasUserIdColumn($model)
                && ! $model->user_id
                && ($user = Auth::user())
            ) {
                $model->user_id = $user->id;
            }
        });
    }

    public function scopeForOutlet(Builder $query, int $outletId): Builder
    {
        return $query->withoutGlobalScope(OutletScope::class)->where('outlet_id', $outletId);
    }

    protected static function tableHasUserIdColumn($model): bool
    {
        $table = $model->getTable();

        if (! array_key_exists($table, static::$tableHasUserColumnCache)) {
            static::$tableHasUserColumnCache[$table] = Schema::hasColumn($table, 'user_id');
        }

        return static::$tableHasUserColumnCache[$table];
    }
}
