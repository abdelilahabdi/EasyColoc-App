<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Payment;
use App\Services\PaymentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


 // Controller pour la gestion des paiements
  
 //Actions disponibles
 // markAsPaid   Marque un paiement comme payE
 
class PaymentController extends Controller
{
    
     // PaymentService injecté
     
    protected PaymentService $paymentService;

    
     // Constructeur avec injection de dépendance
     
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Marque un paiement comme paye
     * 
     * Verifications:
     * - L'utilisateur est autorisé via PaymentPolicy
     * - Le paiement existe et appartient à la colocation
     * - Transaction DB pour la sécurité
     * 
     * @param Colocation $colocation
     * @param Payment $payment
     * @return RedirectResponse
     */
    public function markAsPaid(Colocation $colocation, Payment $payment): RedirectResponse
    {
        // Vérifier que le paiement lier a cette colocation
        if ($payment->colocation_id !== $colocation->id) {
            abort(403, 'Ce paiement n\'appartient pas à cette colocation.');
        }

        // Autorisation via Policy
        $this->authorize('markAsPaid', $payment);

        // Transaction DB pour sécuriser l'opération
        try {
            $this->paymentService->markAsPaid($payment);

            return redirect()->back()->with('success', 'Paiement marqué comme payé.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors du traitement du paiement: ' . $e->getMessage());
        }
    }

    /**
     * Affiche la liste des paiements d'une colocation
     * 
     * @param Colocation $colocation
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function index(Colocation $colocation)
    {
        // Vérifier que l'utilisateur peut voir la colocation
        $this->authorize('view', $colocation);

        $payments = $colocation->payments()
            ->with(['fromUser', 'toUser'])
            ->orderBy('payment_date', 'desc')
            ->get();

        return view('colocations.payments.index', [
            'colocation' => $colocation,
            'payments' => $payments,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un paiement
     * 
     * @param Colocation $colocation
     * @return \Illuminate\View\View|\Illuminate\Http\RedirectResponse
     */
    public function create(Colocation $colocation)
    {
        // Vérifier que l'utilisateur peut créer un paiement
        $this->authorize('create', [Payment::class, $colocation]);

        $members = $colocation->activeMembers()->get();

        return view('colocations.payments.create', [
            'colocation' => $colocation,
            'members' => $members,
        ]);
    }

    /**
     * Crée un nouveau paiement
     * 
     * @param Request $request
     * @param Colocation $colocation
     * @return RedirectResponse
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        // Vérifier que l'utilisateur peut créer un paiement
        $this->authorize('create', [Payment::class, $colocation]);

        $validated = $request->validate([
            'to_user_id' => 'required|exists:users,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
        ]);

        // Vérifier que le destinataire est un membre de la colocation
        $toUserMembership = $colocation->users()->where('user_id', $validated['to_user_id'])->first();
        if (!$toUserMembership || $toUserMembership->pivot->left_at !== null) {
            return redirect()->back()
                ->with('error', 'Le destinataire doit être un membre actif de la colocation.');
        }

        try {
            $colocation->payments()->create([
                'from_user_id' => auth()->id(),
                'to_user_id' => $validated['to_user_id'],
                'amount' => $validated['amount'],
                'payment_date' => $validated['payment_date'],
            ]);

            return redirect()->route('colocations.show', $colocation)
                ->with('success', 'Paiement créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Erreur lors de la création du paiement: ' . $e->getMessage());
        }
    }
}
