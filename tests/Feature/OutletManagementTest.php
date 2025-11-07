<?php

namespace Tests\Feature;

use App\Mail\PartnerInvitationMail;
use App\Models\Outlet;
use App\Models\OutletUserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class OutletManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_create_outlet(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('outlets.store'), [
            'name' => 'Outlet Test',
            'code' => 'OUT-TEST',
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('outlets', [
            'name' => 'Outlet Test',
            'code' => 'OUT-TEST',
        ]);

        $outlet = Outlet::where('code', 'OUT-TEST')->firstOrFail();

        $this->assertDatabaseHas('outlet_user_roles', [
            'outlet_id' => $outlet->id,
            'user_id' => $user->id,
            'role' => 'owner',
            'status' => 'active',
        ]);
    }

    public function test_owner_can_invite_partner(): void
    {
        $owner = User::factory()->create();
        $partner = User::factory()->create();

        Mail::fake();

        $outlet = Outlet::create([
            'name' => 'Outlet A',
            'created_by' => $owner->id,
        ]);

        OutletUserRole::create([
            'outlet_id' => $outlet->id,
            'user_id' => $owner->id,
            'role' => 'owner',
            'status' => 'active',
            'can_manage_stock' => true,
            'can_manage_expense' => true,
            'can_manage_sales' => true,
            'accepted_at' => now(),
        ]);

        $response = $this->actingAs($owner)->post(route('outlets.partners.store', $outlet), [
            'email' => $partner->email,
            'can_manage_stock' => true,
            'can_manage_expense' => false,
            'can_manage_sales' => false,
        ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('outlet_user_roles', [
            'outlet_id' => $outlet->id,
            'user_id' => $partner->id,
            'role' => 'partner',
            'status' => 'pending',
        ]);

        Mail::assertQueued(PartnerInvitationMail::class);
    }
}
