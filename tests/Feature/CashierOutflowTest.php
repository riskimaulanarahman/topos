<?php

namespace Tests\Feature;

use App\Models\CashierSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashierOutflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_cashier_outflow(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cashier/open', [
            'opening_balance' => 50000,
        ])->assertCreated();

        $session = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $this->postJson('/api/cashier/outflows', [
            'amount' => 15000,
            'category' => 'operasional',
            'note' => 'Beli air galon',
            'session_id' => $session?->id,
            'client_id' => 'test-client-create',
            'recorded_at' => now()->toIso8601String(),
        ])->assertCreated()
            ->assertJsonPath('data.cashier_session_id', $session?->id)
            ->assertJsonPath('data.amount', 15000.0)
            ->assertJsonPath('data.category', 'operasional');

        $this->assertDatabaseHas('cashier_outflows', [
            'cashier_session_id' => $session?->id,
            'amount' => 15000,
            'category' => 'operasional',
            'client_id' => 'test-client-create',
        ]);
    }

    public function test_user_can_sync_offline_outflows(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cashier/open', [
            'opening_balance' => 75000,
        ])->assertCreated();

        $session = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->first();

        $payload = [
            'outflows' => [
                [
                    'client_id' => 'offline-1',
                    'session_id' => $session?->id,
                    'amount' => 10000,
                    'category' => 'operasional',
                    'note' => 'Beli kertas struk',
                    'recorded_at' => now()->subMinutes(10)->toIso8601String(),
                ],
                [
                    'client_id' => 'offline-2',
                    'session_id' => $session?->id,
                    'amount' => 5000,
                    'category' => 'lainnya',
                    'note' => 'Tip kurir',
                    'recorded_at' => now()->subMinutes(5)->toIso8601String(),
                ],
            ],
        ];

        $this->postJson('/api/cashier/outflows/sync', $payload)
            ->assertOk()
            ->assertJsonPath('data.0.client_id', 'offline-1')
            ->assertJsonPath('data.0.status', 'synced')
            ->assertJsonPath('data.1.client_id', 'offline-2')
            ->assertJsonPath('data.1.status', 'synced');

        $this->assertDatabaseHas('cashier_outflows', [
            'cashier_session_id' => $session?->id,
            'client_id' => 'offline-1',
            'amount' => 10000,
        ]);

        $this->assertDatabaseHas('cashier_outflows', [
            'cashier_session_id' => $session?->id,
            'client_id' => 'offline-2',
            'amount' => 5000,
        ]);
    }

    public function test_user_can_fetch_outflow_history(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $this->postJson('/api/cashier/open', [
            'opening_balance' => 100000,
        ])->assertCreated();

        $firstSession = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->firstOrFail();

        $this->postJson('/api/cashier/outflows', [
            'amount' => 15000,
            'category' => 'operasional',
            'note' => 'Beli tisu',
            'session_id' => $firstSession->id,
            'recorded_at' => now()->subMinutes(30)->toIso8601String(),
        ])->assertCreated();

        $this->postJson('/api/cashier/close', [
            'closing_balance' => 85000,
        ])->assertOk();

        $this->postJson('/api/cashier/open', [
            'opening_balance' => 50000,
        ])->assertCreated();

        $secondSession = CashierSession::where('user_id', $user->id)
            ->where('status', 'open')
            ->firstOrFail();

        $this->postJson('/api/cashier/outflows', [
            'amount' => 7000,
            'category' => 'lainnya',
            'note' => 'Tip kurir',
            'session_id' => $secondSession->id,
            'recorded_at' => now()->toIso8601String(),
        ])->assertCreated();

        $response = $this->getJson('/api/cashier/outflows/history');

        $response->assertOk()
            ->assertJsonPath('data.0.session.id', $secondSession->id)
            ->assertJsonPath('data.0.outflows.0.amount', 7000.0)
            ->assertJsonPath('data.1.session.id', $firstSession->id)
            ->assertJsonPath('data.1.outflows.0.amount', 15000.0);
    }
}
