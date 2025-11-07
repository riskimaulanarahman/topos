<?php

namespace App\Enums;

enum SubscriptionStatus: string
{
    case TRIALING = 'trialing';
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case CANCELLED = 'cancelled';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::TRIALING => 'Trial',
            self::ACTIVE => 'Aktif',
            self::EXPIRED => 'Kedaluwarsa',
            self::CANCELLED => 'Dibatalkan',
        };
    }
}
