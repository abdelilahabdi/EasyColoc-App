<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Colocation;
use App\Models\Expense;
use App\Models\Settlement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReputationFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $member;
    protected Colocation $colocation;
    protected Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::factory()->create(['reputation' => 0]);
        $this->member = User::factory()->create(['reputation' => 0]);

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
    }

    public function test_member_leaving_with_debt_loses_one_reputation_point(): void
    {
        $this->createExpense($this->owner, 100.00);

        $response = $this->actingAs($this->member)
            ->post(route('colocations.leave', $this->colocation));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(-1, $this->member->fresh()->reputation);
    }

    public function test_member_leaving_without_debt_gains_one_reputation_point(): void
    {
        $this->createExpense($this->owner, 100.00);
        $this->createCompletedSettlement($this->member, $this->owner, 50.00);

        $response = $this->actingAs($this->member)
            ->post(route('colocations.leave', $this->colocation));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(1, $this->member->fresh()->reputation);
    }

    public function test_owner_removing_member_with_debt_penalizes_both_users(): void
    {
        $this->createExpense($this->owner, 100.00);

        $response = $this->actingAs($this->owner)
            ->delete(route('colocations.members.destroy', [
                'colocation' => $this->colocation,
                'memberId' => $this->member->id,
            ]));

        $response->assertRedirect(route('colocations.show', $this->colocation));
        $this->assertSame(-1, $this->member->fresh()->reputation);
        $this->assertSame(-1, $this->owner->fresh()->reputation);
    }

    public function test_owner_removing_member_without_debt_rewards_member_only(): void
    {
        $this->createExpense($this->owner, 100.00);
        $this->createCompletedSettlement($this->member, $this->owner, 50.00);

        $response = $this->actingAs($this->owner)
            ->delete(route('colocations.members.destroy', [
                'colocation' => $this->colocation,
                'memberId' => $this->member->id,
            ]));

        $response->assertRedirect(route('colocations.show', $this->colocation));
        $this->assertSame(1, $this->member->fresh()->reputation);
        $this->assertSame(0, $this->owner->fresh()->reputation);
    }

    public function test_owner_cancelling_with_debt_loses_one_reputation_point(): void
    {
        $this->createExpense($this->member, 100.00);

        $response = $this->actingAs($this->owner)
            ->post(route('colocations.cancel', $this->colocation));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(-1, $this->owner->fresh()->reputation);
    }

    public function test_owner_cancelling_without_debt_gains_one_reputation_point(): void
    {
        $response = $this->actingAs($this->owner)
            ->post(route('colocations.cancel', $this->colocation));

        $response->assertRedirect(route('dashboard'));
        $this->assertSame(1, $this->owner->fresh()->reputation);
    }

    private function createExpense(User $payer, float $amount): void
    {
        Expense::create([
            'title' => 'Depense reputation',
            'amount' => $amount,
            'expense_date' => '2026-03-02',
            'payer_id' => $payer->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);
    }

    private function createCompletedSettlement(User $sender, User $receiver, float $amount): void
    {
        Settlement::create([
            'sender_id' => $sender->id,
            'receiver_id' => $receiver->id,
            'colocation_id' => $this->colocation->id,
            'amount' => $amount,
            'settlement_date' => '2026-03-02',
            'status' => Settlement::STATUS_COMPLETED,
        ]);
    }
}
