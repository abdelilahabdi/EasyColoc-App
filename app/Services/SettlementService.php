<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\User;

/**
 * Service de calcul des règlements ("Qui doit à qui")
 * 
 * Ce service:
 * - Calcule dynamiquement les soldes des membres
 * - Optimise le nombre de transactions nécessaire pour solder les dettes
 * - Ne stocke rien en base (calcul à la demande)
 * - Respecte les règles métier: membres actifs uniquement
 * 
 * Architecture:
 * - Injection de BalanceService pour les calculs de balance
 * - Algorithme greedy pour minimiser les transactions
 * - Code testable et découplé
 */
class SettlementService
{
    /**
     * BalanceService injecté pour récupérer les balances dynamiques
     */
    protected BalanceService $balanceService;

    /**
     * Montant minimal pour une transaction significative
     */
    protected const MIN_AMOUNT = 0.01;

    /**
     * Constructeur avec injection de dépendance
     * 
     * @param BalanceService $balanceService
     */
    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Génère les règlements pour une colocation
     * 
     * Algorithme:
     * 1. Récupère les balances dynamiques depuis BalanceService
     * 2. Filtre les membres actifs uniquement
     * 3. Sépare créditeurs (balance > 0) et débiteurs (balance < 0)
     * 4. Applique l'algorithme greedy pour optimiser les transactions
     * 5. Retourne un tableau structuré des règlements
     * 
     * @param Colocation $colocation
     * @return array<int, array{from_user_id: int, to_user_id: int, amount: float}>
     */
    public function generateSettlements(Colocation $colocation): array
    {
        // Récupère les balances dynamiques depuis BalanceService
        $balances = $this->balanceService->calculateBalances($colocation);

        // Cas edge: pas de balances
        if (empty($balances)) {
            return [];
        }

        // Filtre les soldes non nuls (membres déjà réglés)
        $nonZeroBalances = array_filter($balances, function ($balance) {
            return $balance['balance'] != 0;
        });

        // Cas edge: tous les membres sont déjà réglés
        if (empty($nonZeroBalances)) {
            return [];
        }

        // Sépare créditeurs et débiteurs
        $debtors = [];    // Utilisateurs qui doivent de l'argent (balance négative)
        $creditors = [];  // Utilisateurs à qui on doit de l'argent (balance positive)

        foreach ($balances as $userId => $balanceData) {
            $balance = $balanceData['balance'];

            if ($balance < 0) {
                // Stocke la valeur absolue pour les débiteurs
                $debtors[] = [
                    'user_id' => $userId,
                    'amount' => round(abs($balance), 2)
                ];
            } elseif ($balance > 0) {
                $creditors[] = [
                    'user_id' => $userId,
                    'amount' => round($balance, 2)
                ];
            }
        }

        // Cas edge: pas de débiteurs ou pas de créditeurs
        if (empty($debtors) || empty($creditors)) {
            return [];
        }

        // Trie par montant décroissant pour un matching optimal (greedy)
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        // Applique l'algorithme greedy pour minimiser les transactions
        return $this->optimizeTransactions($debtors, $creditors);
    }

    /**
     * Génère les règlements en tenant compte d'une dette imputée
     * 
     * Utilisé lorsqu'un owner retire un membre avec une dette:
     * - La dette du membre retiré est imputée au owner
     * - Le owner devient responsable de cette dette
     * 
     * @param Colocation $colocation
     * @param int $removedMemberId ID du membre retiré
     * @return array<int, array{from_user_id: int, to_user_id: int, amount: float}>
     */
    public function generateSettlementsWithDebtImputation(
        Colocation $colocation,
        int $removedMemberId
    ): array {
        // Récupère les balances dynamiques
        $balances = $this->balanceService->calculateBalances($colocation);

        if (empty($balances)) {
            return [];
        }

        // Vérifie si le membre retiré avait une dette
        if (!isset($balances[$removedMemberId])) {
            // Membre non trouvé ou pas de balance - retourner les règlements normaux
            return $this->generateSettlements($colocation);
        }

        $removedMemberBalance = $balances[$removedMemberId]['balance'];

        // Si le membre retiré doit de l'argent (balance négative)
        if ($removedMemberBalance < 0) {
            $debtAmount = abs($removedMemberBalance);

            // Impute la dette au owner de la colocation
            $ownerId = $colocation->owner_id;

            if ($ownerId && isset($balances[$ownerId])) {
                // Ajoute la dette au owner (réduit ce que le owner doit recevoir)
                $balances[$ownerId]['balance'] = round(
                    $balances[$ownerId]['balance'] - $debtAmount,
                    2
                );
            }
        }

        // Supprime le membre retiré des balances
        unset($balances[$removedMemberId]);

        // Génère les règlements avec les balances modifiées
        return $this->generateSettlementsFromBalances($balances);
    }

    /**
     * Génère les règlements à partir d'un tableau de balances
     * 
     * Méthode protégée utilisée en interne
     * 
     * @param array $balances
     * @return array<int, array{from_user_id: int, to_user_id: int, amount: float}>
     */
    protected function generateSettlementsFromBalances(array $balances): array
    {
        // Filtre les soldes non nuls
        $nonZeroBalances = array_filter($balances, function ($balanceData) {
            return $balanceData['balance'] != 0;
        });

        if (empty($nonZeroBalances)) {
            return [];
        }

        // Sépare créditeurs et débiteurs
        $debtors = [];
        $creditors = [];

        foreach ($balances as $userId => $balanceData) {
            $balance = $balanceData['balance'];

            if ($balance < 0) {
                $debtors[] = [
                    'user_id' => $userId,
                    'amount' => round(abs($balance), 2)
                ];
            } elseif ($balance > 0) {
                $creditors[] = [
                    'user_id' => $userId,
                    'amount' => round($balance, 2)
                ];
            }
        }

        if (empty($debtors) || empty($creditors)) {
            return [];
        }

        // Trie par montant décroissant
        usort($debtors, fn($a, $b) => $b['amount'] <=> $a['amount']);
        usort($creditors, fn($a, $b) => $b['amount'] <=> $a['amount']);

        return $this->optimizeTransactions($debtors, $creditors);
    }

    /**
     * Optimise les transactions avec l'algorithme greedy
     * 
     * Cet algorithme:
     * - Associe toujours le plus gros débiteur au plus gros créditeur
     * - Minimise le nombre total de transactions
     * - Utilise des arrondis pour la précision décimale
     * 
     * @param array $debtors
     * @param array $creditors
     * @return array<int, array{from_user_id: int, to_user_id: int, amount: float}>
     */
    protected function optimizeTransactions(array $debtors, array $creditors): array
    {
        $transactions = [];

        $i = 0; // Index du débiteur
        $j = 0; // Index du créditeur

        while ($i < count($debtors) && $j < count($creditors)) {
            $debtorAmount = $debtors[$i]['amount'];
            $creditorAmount = $creditors[$j]['amount'];

            // Calcule le montant de la transaction (minimum des deux)
            $transactionAmount = round(min($debtorAmount, $creditorAmount), 2);

            // Ajoute la transaction si le montant est significatif
            if ($transactionAmount > self::MIN_AMOUNT) {
                $transactions[] = [
                    'from_user_id' => $debtors[$i]['user_id'],
                    'to_user_id' => $creditors[$j]['user_id'],
                    'amount' => $transactionAmount,
                ];
            }

            // Met à jour les montants restants
            $debtors[$i]['amount'] = round($debtorAmount - $transactionAmount, 2);
            $creditors[$j]['amount'] = round($creditorAmount - $transactionAmount, 2);

            // Passe au prochain débiteur si réglé
            if ($debtors[$i]['amount'] < self::MIN_AMOUNT) {
                $i++;
            }

            // Passe au prochain créditeur si entièrement payé
            if ($creditors[$j]['amount'] < self::MIN_AMOUNT) {
                $j++;
            }
        }

        return $transactions;
    }

    /**
     * Calcule le montant total des règlements
     * 
     * Utile pour afficher le total à settler
     * 
     * @param array $settlements
     * @return float
     */
    public function calculateTotalSettlements(array $settlements): float
    {
        return round(array_sum(array_column($settlements, 'amount')), 2);
    }

    /**
     * Vérifie si tous les règlements sont effectués
     * 
     * @param array $settlements
     * @return bool
     */
    public function isSettled(array $settlements): bool
    {
        return empty($settlements);
    }
}
