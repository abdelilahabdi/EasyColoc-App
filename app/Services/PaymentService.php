<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Settlement;
use Illuminate\Support\Facades\DB;


class PaymentService
{
    
    protected BalanceService $balanceService;

    
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     
     * @throws \RuntimeException
     */
    public function markAsPaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            $payment->loadMissing(['colocation']);

            $this->ensurePaymentCanBeSettled($payment);
            $this->createSettlementFromPayment($payment);

            return $payment;
        });
    }

    
    protected function createSettlementFromPayment(Payment $payment): void
    {
        $payment->colocation->settlements()->create([
            'sender_id' => $payment->from_user_id,
            'receiver_id' => $payment->to_user_id,
            'amount' => $payment->amount,
            'settlement_date' => $payment->payment_date,
            'status' => Settlement::STATUS_COMPLETED,
        ]);
    }

    /**
     * @throws \RuntimeException
     */
    protected function ensurePaymentCanBeSettled(Payment $payment): void
    {
        if (!$payment->colocation) {
            throw new \RuntimeException('Le paiement ne possede aucune colocation associee.');
        }

        $alreadySettled = $payment->colocation->settlements()
            ->completed()
            ->where('sender_id', $payment->from_user_id)
            ->where('receiver_id', $payment->to_user_id)
            ->where('amount', $payment->amount)
            ->whereDate('settlement_date', $payment->payment_date)
            ->exists();

        if ($alreadySettled) {
            throw new \RuntimeException('Ce paiement a deja ete marque comme paye.');
        }
    }

    /**
    
     * @return array<int, array{total_paid: float, share: float, balance: float}>
     */
    public function calculateBalancesAfterPayment(Payment $payment): array
    {
        if (!$payment->colocation) {
            return [];
        }

        return $this->balanceService->calculateBalances($payment->colocation);
    }

    
    public function paymentSettlesAllDebts(Payment $payment): bool
    {
        if (!$payment->colocation) {
            return true;
        }

        $balances = $this->balanceService->calculateBalances($payment->colocation);

        if (!isset($balances[$payment->from_user_id])) {
            return true;
        }

        return $balances[$payment->from_user_id]['balance'] >= 0;
    }
}
