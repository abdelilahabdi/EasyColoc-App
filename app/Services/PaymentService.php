<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Colocation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Service de gestion des paiements
 * 
 * Ce service:
 * - Enregistre les paiements dans la table payments
 * - Met à jour dynamiquement les balances via BalanceService
 * - Applique les règles de réputation via ReputationService
 * 
 * Architecture:
 * - Injection de BalanceService pour les calculs de balance
 * - Injection de ReputationService pour les règles de réputation
 * - Transaction DB pour assurer l'intégrité des données
 */
class PaymentService
{
    /**
     * BalanceService injecté pour récupérer les balances dynamiques
     */
    protected BalanceService $balanceService;

    /**
     * ReputationService injecté pour les règles de réputation
     */
    protected ReputationService $reputationService;

    /**
     * Constructeur avec injection de dépendance
     */
    public function __construct(
        BalanceService $balanceService,
        ReputationService $reputationService
    ) {
        $this->balanceService = $balanceService;
        $this->reputationService = $reputationService;
    }

    /**
     * Marque un paiement comme payé et met à jour les balances
     * 
     * Cette méthode:
     * 1. Enregistre le paiement dans la table payments
     * 2. Crée un règlement (Settlement) pour solder la dette
     * 3. Met à jour les balances via BalanceService
     * 4. Applique les règles de réputation
     * 
     * @param Payment $payment Le paiement à marquer comme payé
     * @return Payment Le paiement mis à jour
     * @throws \Exception Si le paiement échoue
     */
    public function markAsPaid(Payment $payment): Payment
    {
        return DB::transaction(function () use ($payment) {
            // 1. Marquer le paiement comme payé (si le modèle le supportait)
            // Note: La table payments n'a pas de champ 'is_paid' mais on peut
            // stocker la date de paiement

            // 2. Créer un règlement (Settlement) pour solder la dette
            $this->createSettlementFromPayment($payment);

            // 3. Les balances sont calculées dynamiquement par BalanceService
            // Pas de mise à jour nécessaire en base

            // 4. Appliquer les règles de réputation
            // Le paiement permet de solder une dette, donc si l'utilisateur
            // qui paie avait une dette, sa réputation peut être améliorée
            $this->applyReputationOnPayment($payment);

            return $payment;
        });
    }

    /**
     * Crée un règlement (Settlement) à partir d'un paiement
     * 
     * @param Payment $payment
     * @return void
     */
    protected function createSettlementFromPayment(Payment $payment): void
    {
        // Créer un règlement pour enregistrer la transaction
        $payment->colocation->settlements()->create([
            'sender_id' => $payment->from_user_id,
            'receiver_id' => $payment->to_user_id,
            'amount' => $payment->amount,
            'settled_at' => now(),
        ]);
    }

    /**
     * Applique les règles de réputation lors d'un paiement
     * 
     * Quand un paiement est effectué:
     * - Si le payeur soldait une dette, sa réputation peut être améliorée
     * - On vérifie si après le paiement, le payeur n'a plus de dette
     * 
     * @param Payment $payment
     * @return void
     */
    protected function applyReputationOnPayment(Payment $payment): void
    {
        $colocation = $payment->colocation;
        $payer = $payment->fromUser;

        if (!$payer || !$colocation) {
            return;
        }

        // Calculer les balances après le paiement
        // Note: BalanceService calcule dynamiquement, donc on doit
        // simuler l'impact du paiement sur les balances

        $balances = $this->balanceService->calculateBalances($colocation);

        // Vérifier si le payeur avait une dette avant le paiement
        // Pour simplifier, on vérifie si le paiement correspond à une dette
        // existante et si après, sa balance est positive ou nulle

        // Cette logique peut être ajustée selon les règles métier souhaitées
        // Pour l'instant, on ne modifie pas la réputation lors d'un paiement
        // car les règles spécifiées concernent le départ/annulation

        // TODO: Implémenter logique spécifique si nécessaire
    }

    /**
     * Calcule les balances après un paiement
     * 
     * @param Payment $payment
     * @return array Les balances mises à jour
     */
    public function calculateBalancesAfterPayment(Payment $payment): array
    {
        $colocation = $payment->colocation;

        // Les balances sont calculées dynamiquement en incluant
        // tous les règlements (settlements) existants
        return $this->balanceService->calculateBalances($colocation);
    }

    /**
     * Vérifie si un paiement sold toutes les dettes d'un utilisateur
     * 
     * @param Payment $payment
     * @return bool True si le paiement sold toutes les dettes
     */
    public function paymentSettlesAllDebts(Payment $payment): bool
    {
        $colocation = $payment->colocation;
        $balances = $this->balanceService->calculateBalances($colocation);

        // Vérifier si le payeur n'a plus de dette après ce paiement
        if (!isset($balances[$payment->from_user_id])) {
            return true;
        }

        return $balances[$payment->from_user_id]['balance'] >= 0;
    }
}
