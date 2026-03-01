<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Colocation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, Colocation $colocation): RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        // Check if the authenticated user is the owner of the colocation
        if (!Gate::allows('update', $colocation)) {
            abort(403, 'Vous n\'êtes pas autorisé à créer une catégorie dans cette colocation.');
        }

        // Create the category associated with the colocation
        $colocation->categories()->create([
            'name' => $validated['name'],
        ]);

        return redirect()->back()
            ->with('success', 'Catégorie créée avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Colocation $colocation, Category $category): RedirectResponse
    {
        // Check if the authenticated user is the owner of the colocation
        if (!Gate::allows('update', $colocation)) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer une catégorie dans cette colocation.');
        }

        // Verify that the category belongs to this colocation
        if ($category->colocation_id !== $colocation->id) {
            abort(403, 'Cette catégorie n\'appartient pas à cette colocation.');
        }

        // Delete the category
        $category->delete();

        return redirect()->back()
            ->with('success', 'Catégorie supprimée avec succès.');
    }
}
