<?php

namespace App\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OutletScope implements Scope
{
    protected static ?int $activeOutletId = null;

    public function apply(Builder $builder, Model $model): void
    {
        if (! static::$activeOutletId) {
            return;
        }

        $builder->where($model->getTable() . '.outlet_id', static::$activeOutletId);
    }

    public static function setActiveOutletId(?int $outletId): void
    {
        static::$activeOutletId = $outletId;
    }

    public static function getActiveOutletId(): ?int
    {
        return static::$activeOutletId;
    }
}
