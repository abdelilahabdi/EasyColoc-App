<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Colocation;
use App\Models\Category;
use App\Models\Expense;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExpenseControllerTest extends TestCase
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

        // Create users
        $this->owner = User::factory()->create();
        $this->member = User::factory()->create();
        $this->outsider = User::factory()->create();

        // Create colocation and add owner
        $this->colocation = Colocation::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $this->colocation->users()->attach($this->owner->id, ['role' => 'owner']);

        // Add member to colocation
        $this->colocation->users()->attach($this->member->id, ['role' => 'member']);

        // Create category
        $this->category = Category::factory()->create(['colocation_id' => $this->colocation->id]);
    }

    /**
     * Test: Store expense - Success (member can create expense)
     */
    public function test_member_can_create_expense(): void
    {
        $response = $this->actingAs($this->member)->postJson("/colocations/{$this->colocation->id}/expenses", [
            'title' => 'Test Expense',
            'amount' => 100.50,
            'expense_date' => '2024-01-15',
            'category_id' => $this->category->id,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Dépense créée avec succès.');

        $this->assertDatabaseHas('expenses', [
            'title' => 'Test Expense',
            'amount' => 100.50,
            'payer_id' => $this->member->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);
    }

    /**
     * Test: Store expense - Validation fails (missing fields)
     */
    public function test_store_expense_validates_required_fields(): void
    {
        $response = $this->actingAs($this->member)->postJson("/colocations/{$this->colocation->id}/expenses", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['title', 'amount', 'expense_date', 'category_id']);
    }

    /**
     * Test: Store expense - Validation fails (category doesn't exist)
     */
    public function test_store_expense_validates_category_exists(): void
    {
        $response = $this->actingAs($this->member)->postJson("/colocations/{$this->colocation->id}/expenses", [
            'title' => 'Test Expense',
            'amount' => 100.50,
            'expense_date' => '2024-01-15',
            'category_id' => 9999,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['category_id']);
    }

    /**
     * Test: Store expense - User is not a member (should fail)
     */
    public function test_outsider_cannot_create_expense(): void
    {
        $response = $this->actingAs($this->outsider)->postJson("/colocations/{$this->colocation->id}/expenses", [
            'title' => 'Test Expense',
            'amount' => 100.50,
            'expense_date' => '2024-01-15',
            'category_id' => $this->category->id,
        ]);

        $response->assertStatus(403);
    }

    /**
     * Test: Destroy expense - Owner can delete any expense
     */
    public function test_owner_can_delete_any_expense(): void
    {
        // Member creates an expense
        $expense = Expense::factory()->create([
            'payer_id' => $this->member->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/colocations/{$this->colocation->id}/expenses/{$expense->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Dépense supprimée avec succès.');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    /**
     * Test: Destroy expense - Creator can delete their own expense
     */
    public function test_creator_can_delete_their_own_expense(): void
    {
        $expense = Expense::factory()->create([
            'payer_id' => $this->member->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->member)->delete("/colocations/{$this->colocation->id}/expenses/{$expense->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Dépense supprimée avec succès.');

        $this->assertDatabaseMissing('expenses', ['id' => $expense->id]);
    }

    /**
     * Test: Destroy expense - Other member cannot delete expense
     */
    public function test_other_member_cannot_delete_expense(): void
    {
        // Create another member
        $otherMember = User::factory()->create();
        $this->colocation->users()->attach($otherMember->id, ['role' => 'member']);

        // Member creates an expense
        $expense = Expense::factory()->create([
            'payer_id' => $this->member->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($otherMember)->delete("/colocations/{$this->colocation->id}/expenses/{$expense->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    /**
     * Test: Destroy expense - Outsider cannot delete expense
     */
        public function test_outsider_cannot_delete_expense(): void
    {
        $expense = Expense::factory()->create([
            'payer_id' => $this->member->id,
            'colocation_id' => $this->colocation->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->outsider)->delete("/colocations/{$this->colocation->id}/expenses/{$expense->id}");

        $response->assertStatus(403);

        $this->assertDatabaseHas('expenses', ['id' => $expense->id]);
    }

    /**
     * Test: Destroy expense - Cannot delete expense from different colocation
     */
    public function test_cannot_delete_expense_from_different_colocation(): void
    {
        $otherColocation = Colocation::factory()->create([
            'owner_id' => $this->owner->id,
        ]);
        $otherColocation->users()->attach($this->owner->id, ['role' => 'owner']);

        $expense = Expense::factory()->create([
            'payer_id' => $this->owner->id,
            'colocation_id' => $otherColocation->id,
            'category_id' => $this->category->id,
        ]);

        $response = $this->actingAs($this->owner)->delete("/colocations/{$this->colocation->id}/expenses/{$expense->id}");

        $response->assertStatus(403);
    }
}
