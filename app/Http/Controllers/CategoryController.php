<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Colocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    
     // khezen wa7ed element jdid f data base 
     
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        // nte2eked wach data li jaya s7i7a 
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // te2eked wach user li dayer login hewa mol colocation 
        if (!Gate::allows('update', $colocation)) {
            abort(403, 'Vous n\'êtes pas autorisé à créer une catégorie dans cette colocation.');
        }

        // nsayeb category merbota b colocation 
        $colocation->categories()->create([
            'name' => $validated['name'],
        ]);

        return redirect()->back()
            ->with('success', 'Catégorie créée avec succès.');
    }

    
     // n7ayed element specified men storage db
     
    public function destroy(Colocation $colocation, Category $category): RedirectResponse
    {
        // te2eked wach user li dayer login hewa mol collocation 
        if (!Gate::allows('update', $colocation)) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer une catégorie dans cette colocation.');
        }

        // nte2eked wach had category tabe3a l had colocation
        if ($category->colocation_id !== $colocation->id) {
            abort(403, 'Cette catégorie n\'appartient pas à cette colocation.');
        }

        // delet category
        $category->delete();

        return redirect()->back()
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
