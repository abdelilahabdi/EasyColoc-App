<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $member;
    protected User $outsider;
    protected Colocation $colocation;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['reputation' => 0]);
        $this->member = User::factory()->create(['reputation' => 0]);
        $this->outsider = User::factory()->create();

        $this->colocation = Colocation::factory()->create([
            'owner_id' => $this->owner->id,
            'status' => 'active',
        ]);

        $this->colocation->users()->attach($this->owner->id, [
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        $this->colocation->users()->attach($this->member->id, [
            'role' => 'member',
            'joined_at' => now(),
        ]);

        $this->category = Category::factory()->create([
            'colocation_id' => $this->colocation->id,
        ]);

        $this->createExpense($this->owner, 100.00);
    }

    public function test_member_can_mark_settlement_as_paid_for_existing_debt(): void
    {
        $response = $this->actingAs($this->member)->post(route('settlements.store', $this->colocation), [
            'sender_id' => $this->member->id,
            'receiver_id' => $this->owner->id,
            'amount' => 50.00,
            'settlement_date' => '2026-03-02',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Paiement marque comme paye avec succes.');

        $this->assertDatabaseHas('settlements', [
            'colocation_id' => $this->colocation->id,
            'sender_id' => $this->member->id,
            'receiver_id' => $this->owner->id,
            'amount' => 50.00,
            'status' => Settlement::STATUS_COMPLETED,
        ]);

        $this->assertSame([], $this->colocation->fresh()->getSimplifiedDebts());

        $balances = $this->colocation->fresh()->calculateBalancesWithSettlements();

        $this->assertSame(0.0, $balances[$this->owner->id]);
        $this->assertSame(0.0, $balances[$this->member->id]);
        $this->assertSame(0, $this->member->fresh()->reputation);
    }

    public function test_store_rejects_settlement_that_does_not_match_current_debt(): void
    {
        $response = $this->actingAs($this->member)->postJson(route('settlements.store', $this->colocation), [
            'sender_id' => $this->owner->id,
            'receiver_id' => $this->member->id,
            'amount' => 50.00,
            'settlement_date' => '2026-03-02',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['amount']);

        $this->assertDatabaseCount('settlements', 0);
    }

    public function test_receiver_can_confirm_pending_settlement(): void
    {
        $settlement = $this->createPendingSettlement();

        $response = $this->actingAs($this->owner)->post(route('settlements.confirm', $settlement));

        $response->assertRedirect(route('colocations.show', $this->colocation));
        $response->assertSessionHas('success', 'Paiement confirme avec succes.');

        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'status' => Settlement::STATUS_COMPLETED,
        ]);

        $this->assertSame(0, $this->member->fresh()->reputation);
    }

    public function test_sender_cannot_confirm_their_own_settlement(): void
    {
        $settlement = $this->createPendingSettlement();

        $response = $this->actingAs($this->member)->post(route('settlements.confirm', $settlement));

        $response->assertForbidden();

        $this->assertDatabaseHas('settlements', [
            'id' => $settlement->id,
            'status' => Settlement::STATUS_PENDING,
        ]);
    }

    public function test_confirm_rejects_stale_pending_settlement(): void
    {
        $pendingSettlement = $this->createPendingSettlement();

        Settlement::create([
            'sender_id' => $this->member->id,
            'receiver_id' => $this->owner->id,
            'colocation_id' => $this->colocation->id,
            'amount' => 50.00,
            'settlement_date' => '2026-03-01',
            'status' => Settlement::STATUS_COMPLETED,
        ]);

        $response = $this->actingAs($this->owner)->post(route('settlements.confirm', $pendingSettlement));

        $response->assertRedirect(route('colocations.show', $this->colocation));
        $response->assertSessionHas('error', 'Ce paiement ne correspond plus a la dette restante. Veuillez actualiser la page.');

        $this->assertDatabaseHas('settlements', [
            'id' => $pendingSettlement->id,
            'status' => Settlement::STATUS_PENDING,
        ]);

        $this->assertSame(0, $this->member->fresh()->reputation);
    }

    private function createExpense(User $payer, float $amount): void
    {
        Expense::create([
            'title' => 'Depense de test',
            'amount' => $amount,
            'expense_date' => '2026-03-01',
            'payer_id' => $payer->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);
    }

    private function createPendingSettlement(): Settlement
    {
        return Settlement::create([
            'sender_id' => $this->member->id,
            'receiver_id' => $this->owner->id,
            'colocation_id' => $this->colocation->id,
            'amount' => 50.00,
            'settlement_date' => '2026-03-02',
            'status' => Settlement::STATUS_PENDING,
        ]);
    }
}
