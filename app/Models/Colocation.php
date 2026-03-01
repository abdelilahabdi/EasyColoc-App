<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colocation extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'owner_id', 'status'];

    // ==================== RELATIONSHIPS ====================

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'colocation_user')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function settlements(): HasMany
    {
        return $this->hasMany(Settlement::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function activeMembers(): BelongsToMany
    {
        return $this->users()->wherePivot('left_at', null);
    }

    // ==================== FINANCIAL CALCULATIONS ====================

    /**
     * Calculate individual balances for all active members.
     * 
     * Formula: Balance = (Total Paid) - (Fair Share)
     * 
     * @return array [user_id => balance]
     *               Positive (+) = CREDITOR (is owed money)
     *               Negative (-) = DEBTOR (owes money)
     *               Sum of all balances = 0
     */
    public function calculateBalances(): array
    {
        $activeMembers = $this->activeMembers()->get(['users.id', 'users.name']);
        $memberCount = $activeMembers->count();

        if ($memberCount === 0) {
            return [];
        }

        $totalExpenses = (float) $this->expenses()->sum('amount');

        if ($totalExpenses == 0) {
            return $activeMembers->pluck('id')->mapWithKeys(fn($id) => [$id => 0.0])->toArray();
        }

        $fairShare = round($totalExpenses / $memberCount, 2);
        $balances = [];

        foreach ($activeMembers as $member) {
            $memberPaid = (float) $this->expenses()->where('payer_id', $member->id)->sum('amount');
            $balances[$member->id] = round($memberPaid - $fairShare, 2);
        }

        return $balances;
    }

    /**
     * Calculate balances after deducting settlements already made.
     * 
     * This method adjusts the raw balances by subtracting settlements:
     * - If member received money (settlement receiver), reduce their credit
     * - If member paid money (settlement sender), reduce their debt
     * 
     * @return array [user_id => adjusted_balance]
     */
    public function calculateBalancesWithSettlements(): array
    {
        $balances = $this->calculateBalances();

        if (empty($balances)) {
            return [];
        }

        $settlementAdjustments = $this->calculateSettlementAdjustments();

        foreach ($balances as $userId => $balance) {
            $adjustment = $settlementAdjustments[$userId] ?? 0;
            $balances[$userId] = round($balance - $adjustment, 2);
        }

        return $balances;
    }

    /**
     * Calculate net settlement adjustments for each member.
     * 
     * @return array [user_id => net_amount]
     *               Positive = received money
     *               Negative = paid money
     */
    private function calculateSettlementAdjustments(): array
    {
        $settlements = $this->settlements()->get();
        $adjustments = [];

        foreach ($settlements as $settlement) {
            $adjustments[$settlement->receiver_id] = ($adjustments[$settlement->receiver_id] ?? 0) + $settlement->amount;
            $adjustments[$settlement->sender_id] = ($adjustments[$settlement->sender_id] ?? 0) - $settlement->amount;
        }

        return $adjustments;
    }

    /**
     * Get simplified debts using Greedy Algorithm.
     * 
     * Minimizes the number of transactions needed to settle all debts.
     * Uses balances WITH settlements deducted.
     * 
     * @return array [['from' => user_id, 'to' => user_id, 'amount' => float], ...]
     */
    public function getSimplifiedDebts(): array
    {
        $balances = $this->calculateBalancesWithSettlements();

        if (empty($balances)) {
            return [];
        }

        // Load active members to ensure users exist
        $activeUserIds = $this->activeMembers()->pluck('id')->toArray();

        $debtors = [];
        $creditors = [];

        foreach ($balances as $userId => $balance) {
            // Only include active members
            if (!in_array($userId, $activeUserIds)) {
                continue;
            }

            if ($balance < -0.01) {
                $debtors[] = ['id' => $userId, 'amount' => round(abs($balance), 2)];
            } elseif ($balance > 0.01) {
                $creditors[] = ['id' => $userId, 'amount' => round($balance, 2)];
            }
        }

        if (empty($debtors) || empty($creditors)) {
            return [];
        }

        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return $this->greedyDebtSimplification($debtors, $creditors);
    }

    /**
     * Greedy algorithm to minimize transactions.
     * 
     * @param array $debtors [['id' => int, 'amount' => float], ...]
     * @param array $creditors [['id' => int, 'amount' => float], ...]
     * @return array [['from' => int, 'to' => int, 'amount' => float], ...]
     */
    private function greedyDebtSimplification(array $debtors, array $creditors): array
    {
        $transactions = [];
        $i = 0;
        $j = 0;

        while ($i < count($debtors) && $j < count($creditors)) {
            $transactionAmount = round(min($debtors[$i]['amount'], $creditors[$j]['amount']), 2);

            if ($transactionAmount > 0.01) {
                $transactions[] = [
                    'from' => $debtors[$i]['id'],
                    'to' => $creditors[$j]['id'],
                    'amount' => $transactionAmount,
                ];
            }

            $debtors[$i]['amount'] = round($debtors[$i]['amount'] - $transactionAmount, 2);
            $creditors[$j]['amount'] = round($creditors[$j]['amount'] - $transactionAmount, 2);

            if ($debtors[$i]['amount'] < 0.01) {
                $i++;
            }

            if ($creditors[$j]['amount'] < 0.01) {
                $j++;
            }
        }

        return $transactions;
    }
}
