<?php

namespace Database\Factories;

use App\Models\CashierOutflow;
use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<CashierOutflow>
 */
class CashierOutflowFactory extends Factory
{
    protected $model = CashierOutflow::class;

    public function definition(): array
    {
        return [
            'cashier_session_id' => CashierSession::factory(),
            'user_id' => function (array $attributes) {
                $session = CashierSession::find($attributes['cashier_session_id']);
                return $session?->user_id ?? User::factory();
            },
            'outlet_id' => function (array $attributes) {
                $session = CashierSession::find($attributes['cashier_session_id']);
                return $session?->outlet_id;
            },
            'client_id' => (string) Str::uuid(),
            'amount' => $this->faker->randomFloat(2, 5000, 500000),
            'category' => $this->faker->randomElement(['operasional', 'refund', 'lainnya']),
            'note' => $this->faker->sentence(),
            'is_offline' => false,
            'recorded_at' => now(),
            'synced_at' => now(),
        ];
    }
}
