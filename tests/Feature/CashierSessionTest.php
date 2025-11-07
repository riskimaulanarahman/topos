<?php

namespace Tests\Feature;

use App\Jobs\SendCashierSummaryEmail;
use App\Models\CashierSession;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CashierSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_open_cashier_session_once(): void
    {
        $user = User::factory()->create([
            'store_name' => 'Demo Store',
        ]);
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/cashier/open', [
            'opening_balance' => 100000,
            'remarks' => 'First shift',
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('cashier_sessions', [
            'user_id' => $user->id,
            'status' => 'open',
        ]);

        $statusResponse = $this->getJson('/api/cashier/status');
        $statusResponse->assertOk()->assertJsonPath('data.status', 'open');

        $secondAttempt = $this->postJson('/api/cashier/open', [
            'opening_balance' => 50000,
        ]);

        $secondAttempt->assertStatus(409);
        $this->assertEquals(1, CashierSession::where('user_id', $user->id)->count());
    }

    public function test_user_can_close_cashier_session(): void
    {
        $user = User::factory()->create([
            'store_name' => 'Demo Store',
        ]);
        Sanctum::actingAs($user);

        Bus::fake();

        $this->postJson('/api/cashier/open', [
            'opening_balance' => 100000,
        ])->assertCreated();

        $session = CashierSession::where('user_id', $user->id)->where('status', 'open')->first();

        $this->postJson('/api/cashier/outflows', [
            'amount' => 20000,
            'category' => 'operasional',
            'note' => 'Beli plastik',
            'session_id' => $session?->id,
            'recorded_at' => now()->toIso8601String(),
            'client_id' => 'test-client',
        ])->assertCreated();

        Order::create([
            'user_id' => $user->id,
            'transaction_number' => 'TRX-1',
            'transaction_time' => now(),
            'total_price' => 120000,
            'total_item' => 3,
            'payment_method' => 'cash',
            'nominal_bayar' => 120000,
            'status' => 'completed',
        ]);

        Order::create([
            'user_id' => $user->id,
            'transaction_number' => 'TRX-2',
            'transaction_time' => now(),
            'total_price' => 80000,
            'total_item' => 2,
            'payment_method' => 'qr',
            'nominal_bayar' => 80000,
            'status' => 'completed',
        ]);

        Order::create([
            'user_id' => $user->id,
            'transaction_number' => 'TRX-3',
            'transaction_time' => now(),
            'total_price' => 50000,
            'total_item' => 1,
            'payment_method' => 'cash',
            'nominal_bayar' => 0,
            'status' => 'refund',
            'refund_method' => 'cash',
            'refund_nominal' => 50000,
        ]);

        $closeResponse = $this->postJson('/api/cashier/close', [
            'closing_balance' => 85000,
            'remarks' => 'Closing shift',
        ]);

        $closeResponse->assertOk()
            ->assertJsonPath('data.session.status', 'closed')
            ->assertJsonPath('data.summary.totals.sales', 200000)
            ->assertJsonPath('data.summary.totals.refunds', 50000)
            ->assertJsonPath('data.summary.totals.net_sales', 150000)
            ->assertJsonPath('data.summary.session.remarks', 'Closing shift')
            ->assertJsonPath('data.summary.cash_balance.opening', 100000)
            ->assertJsonPath('data.summary.cash_balance.cash_sales', 120000)
            ->assertJsonPath('data.summary.cash_balance.cash_refunds', 50000)
            ->assertJsonPath('data.summary.cash_balance.cash_outflows', 20000)
            ->assertJsonPath('data.summary.cash_balance.expected', 150000)
            ->assertJsonPath('data.summary.cash_balance.counted', 85000)
            ->assertJsonPath('data.summary.cash_balance.difference', -65000)
            ->assertJsonPath('data.summary.outflows.total', 20000)
            ->assertJsonPath('data.summary.outflows.by_category.0.category', 'operasional');

        $this->assertDatabaseHas('cashier_sessions', [
            'user_id' => $user->id,
            'status' => 'closed',
        ]);

        $reportId = $closeResponse->json('data.report_id');
        $this->assertNotNull($reportId);

        $this->assertDatabaseHas('cashier_closure_reports', [
            'id' => $reportId,
            'user_id' => $user->id,
            'cashier_session_id' => $session->id,
            'email_status' => 'pending',
        ]);

        $this->assertDatabaseHas('cashier_outflows', [
            'cashier_session_id' => $session->id,
            'amount' => 20000,
            'category' => 'operasional',
        ]);

        Bus::assertDispatched(SendCashierSummaryEmail::class, function (SendCashierSummaryEmail $job) use ($reportId) {
            return $job->reportId === $reportId;
        });

        $statusResponse = $this->getJson('/api/cashier/status');
        $statusResponse->assertOk()->assertJsonPath('data.status', 'closed');

        $reportsResponse = $this->getJson('/api/cashier/reports');
        $reportsResponse->assertOk()
            ->assertJsonPath('data.0.id', $reportId)
            ->assertJsonPath('data.0.summary.cash_balance.difference', -65000);
    }
}
