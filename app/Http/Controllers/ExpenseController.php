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
    
     // Show form for creating new expense
     // gha active members li y9edro create expenses
     
    public function create(Colocation $colocation): View|RedirectResponse
    {
        $this->authorize('create', [Expense::class, $colocation]);

        // jib categirie li tab3in lhad colocation
        $categories = $colocation->categories()->orderBy('name')->get();

        return view('colocations.expenses.create', [
            'colocation' => $colocation,
            'categories' => $categories,
        ]);
    }

    //Store or nekhezen element expense jdid f db 
    
     // gha member active li ye9edro create w yzido expense
     
    public function store(StoreExpenseRequest $request, Colocation $colocation): RedirectResponse
    {
        $this->authorize('create', [Expense::class, $colocation]);

        // te2eked anaho colocation mazal khedam w user makherejch menha
        $membership = $colocation->users()->where('user_id', auth()->id())->first();
        if ($colocation->status !== 'active' || ($membership && $membership->pivot->left_at !== null)) {
            abort(403, 'Vous ne pouvez pas ajouter de dépense à une colocation archivée.');
        }

        // nsayeb expense lier with colocation
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

    
     // 7ayed element specifique men db
     
    public function destroy(Colocation $colocation, Expense $expense): RedirectResponse
    {
        $this->authorize('delete', $expense);

        // Verify had expense merbot b had colocation
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

        // Delet expense
        $expense->delete();

        return redirect()->back()
            ->with('success', 'Dépense supprimée avec succès.');
    }
}

