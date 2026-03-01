<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreExpenseRequest;
use App\Models\Colocation;
use App\Models\Expense;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use App\Models\transaction;

class ExpenseController extends Controller
{
    /**
     * Show the form for creating a new expense.
     * Only active members are authorized to create expenses.
     */
    public function create(Colocation $colocation): View|RedirectResponse
    {
        $this->authorize('create', [Expense::class, $colocation]);

        // Get categories for this colocation
        $categories = $colocation->categories()->orderBy('name')->get();

        return view('colocations.expenses.create', [
            'colocation' => $colocation,
            'categories' => $categories,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     * Only active members are authorized to create expenses.
     */
    public function store(StoreExpenseRequest $request, Colocation $colocation): RedirectResponse
    {
        $this->authorize('create', [Expense::class, $colocation]);

        // Check if colocation is active and user hasn't left
        $membership = $colocation->users()->where('user_id', auth()->id())->first();
        if ($colocation->status !== 'active' || ($membership && $membership->pivot->left_at !== null)) {
            abort(403, 'Vous ne pouvez pas ajouter de dépense à une colocation archivée.');
        }

        // Create the expense associated with the colocation
        $expense = $colocation->expenses()->create([
            'title' => $request->validated('title'),
            'amount' => $request->validated('amount'),
            'expense_date' => $request->validated('expense_date'),
            'category_id' => $request->validated('category_id'),
            'payer_id' => auth()->id(),
        ]);


          $apartment = Auth()->user()->colocations()->wherePivot('left_at', null)->with('users')->first();

        $userCount = $apartment->users->count('id');

        $amountSend = $request->amount / $userCount;

        $apartmentUsers = $apartment->users->where('id', '!=', auth()->id());

        $expensesData = $apartmentUsers->map(function ($user) use ($amountSend, $apartment, $request , $expense) {
            return [
                'expense_id'   => $expense->id,
                'colocation_id' => $apartment->id,
                'debtor_id'    => auth()->id(),
                'creditor_id'  => $user->id,
                'amount'       => $amountSend,
                'status'       => 'pending',
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        });

        transaction::insert($expensesData->toArray());



        return redirect()->route('colocations.show', $colocation)
            ->with('success', 'Dépense créée avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Colocation $colocation, Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        // Verify that the expense belongs to this colocation
        if ($expense->colocation_id !== $colocation->id) {
            abort(403, 'Cette dépense n\'appartient pas à cette colocation.');
        }

        //if ($expense->transactions()->exists()){
          //  abort(403, 'Cette dépense deja a transaction.');
       // }

       if ($expense->transactions()->exists()) {
    return back()->withErrors([
        'error' => 'Impossible de supprimer cette dépense car elle contient des transactions.'
    ]);
}

        // Delete the expense
        $expense->delete();

        return redirect()->back()
            ->with('success', 'Dépense supprimée avec succès.');
    }
}

