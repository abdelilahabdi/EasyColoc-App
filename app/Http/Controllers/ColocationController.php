<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;

class ColocationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('colocations.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        // Validate the request
        $validated = $request->validate([
            'name' => ['required', 'string'],
        ]);

        // Check if the authenticated user is already in an active colocation
        $user = auth()->user();

        // Check if user has any active colocation (where left_at is null)
        $hasActiveColocation = $user->colocations()
            ->wherePivotNull('left_at')
            ->exists();

        if ($hasActiveColocation) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous faites déjà partie d\'une colocation active.');
        }

        // Create new colocation
        $colocation = Colocation::create([
            'name' => $validated['name'],
            'status' => 'active',
        ]);

        // Attach the user as owner
        $user->colocations()->attach($colocation->id, ['role' => 'owner']);

        // Redirect to the show method
        return redirect()->route('colocations.show', $colocation);
    }

    /**
     * Display the specified resource.
     */
    public function show(Colocation $colocation)
    {
        // Retrieve colocation with its members, expenses and categories
        $colocation->load(['users', 'expenses.category']);

        return view('colocations.show', compact('colocation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
