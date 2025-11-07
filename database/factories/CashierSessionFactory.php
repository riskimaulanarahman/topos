<?php

namespace Database\Factories;

use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CashierSession>
 */
class CashierSessionFactory extends Factory
{
    protected $model = CashierSession::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'outlet_id' => null,
            'opening_balance' => $this->faker->randomFloat(2, 50000, 200000),
            'opened_at' => now()->subHours($this->faker->numberBetween(1, 8)),
            'closing_balance' => null,
            'closed_at' => null,
            'opened_by' => null,
            'closed_by' => null,
            'status' => 'open',
            'remarks' => $this->faker->optional()->sentence(),
        ];
    }

    public function closed(): self
    {
        return $this->state(function () {
            return [
                'status' => 'closed',
                'closing_balance' => $this->faker->randomFloat(2, 40000, 150000),
                'closed_at' => now(),
            ];
        });
    }
}
