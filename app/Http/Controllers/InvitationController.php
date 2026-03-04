<?php

namespace App\Http\Controllers;

use App\Models\Colocation;
use App\Models\Invitation;
use App\Services\InvitationService;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

class InvitationController extends Controller
{
    protected InvitationService $invitationService;

    public function __construct(InvitationService $invitationService)
    {
        $this->invitationService = $invitationService;
    }

    
     // Send invitation to join colocation
     
    public function invite(Request $request, Colocation $colocation): RedirectResponse
    {
        $this->authorize('inviteMember', $colocation);

        // te2eked anaho info correcte avant mankhezenha
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        try {
            $this->invitationService->createInvitation(
                $colocation,
                $validated['email'],
                Auth::user()
                //hna ywsel email
            );

            return redirect()->route('colocations.show', $colocation)
                ->with('success', 'Invitation envoyée avec succès!');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('colocations.show', $colocation)
                ->with('error', $e->getMessage());
        }
    }

    
     // Accept an invitation to join a colocation.
     
     // utiliser route model binding with ID, mais validates token for security.  tjib element direct men url checker 3la token b7al invitations bach nedemno lien s7i7 w machi ay wa7ed yeste3mlo 
     
    public function accept(Invitation $invitation): RedirectResponse
    {
        $user = Auth::user();

        // Check  user already has an active colocation
        $hasActiveColocation = $user->colocations()
            ->where('status', 'active')
            ->where(function ($query) use ($user) {
                $query->whereNull('colocation_user.left_at')
                    ->orWhere('colocation_user.left_at', '>', now());
            })
            ->exists();

        if ($hasActiveColocation) {
            return redirect()->route('dashboard')
                ->with('error', 'Vous avez déjà une colocation active.');
        }

        // Validate using service with invitation token
        try {
            $updatedInvitation = $this->invitationService->acceptInvitation($invitation->token, $user);

            return redirect()->route('colocations.show', $updatedInvitation->colocation_id)
                ->with('success', 'Vous avez rejoint la colocation avec succès!');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    
     // refed wa7ed invitation bach join colocation

    public function decline(Invitation $invitation): RedirectResponse
    {
        $user = Auth::user();

        // Verify the invitation dyal user li dayer login 
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation ne vous est pas destinée.');
        }

        try {
            $this->invitationService->refuseInvitation($invitation->token);

            return redirect()->route('dashboard')
                ->with('success', 'Invitation déclinée.');
        } catch (InvalidArgumentException $e) {
            return redirect()->route('dashboard')
                ->with('error', $e->getMessage());
        }
    }

    
     // Show invitation details for preview before accepting
     
    public function show(Invitation $invitation)
    {
        $user = Auth::user();

        // Check if invitation dyal user li dayer login
        if (strtolower($user->email) !== strtolower($invitation->email)) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation ne vous est pas destinée.');
        }

        // Check if invitation is still valid 
        if ($invitation->status !== 'pending') {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation a déjà été traitée.');
        }

        if ($invitation->isExpired()) {
            return redirect()->route('dashboard')
                ->with('error', 'Cette invitation a expiré.');
        }

        $colocation = $invitation->colocation;

        return view('invitations.show', compact('invitation', 'colocation'));
    }
}
