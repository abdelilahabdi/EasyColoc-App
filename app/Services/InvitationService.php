<?php

namespace App\Services;

use App\Models\Colocation;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InvitationService
{
    protected ColocationService $colocationService;

    public function __construct(ColocationService $colocationService)
    {
        $this->colocationService = $colocationService;
    }

    /**
     * Create a new invitation to join a colocation.
     *
     * @param Colocation $colocation The colocation to invite to
     * @param string $email The email address to invite
     * @param User $sender The user sending the invitation
     * @return Invitation The created invitation
     * @throws InvalidArgumentException If validation fails
     */
    public function createInvitation(Colocation $colocation, string $email, User $sender): Invitation
    {
        // Validate email format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('L\'adresse email n\'est pas valide.');
        }

        // Check if colocation is active
        if ($colocation->status !== 'active') {
            throw new InvalidArgumentException('Cette colocation n\'est plus active.');
        }

        // Check if email is already a member of this colocation
        $existingMember = $colocation->users()->where('email', $email)->exists();
        if ($existingMember) {
            throw new InvalidArgumentException('Cet utilisateur est déjà membre de la colocation.');
        }

        // Check for existing pending invitation
        $existingInvitation = Invitation::where('email', $email)
            ->where('colocation_id', $colocation->id)
            ->where('status', 'pending')
            ->first();

        if ($existingInvitation) {
            // Check if existing invitation has expired
            if ($existingInvitation->isExpired()) {
                // Delete the expired invitation
                $existingInvitation->delete();
            } else {
                throw new InvalidArgumentException('Une invitation est déjà en attente pour cet email.');
            }
        }

        // Generate a secure unique token using UUID
        $token = Str::uuid()->toString();

        // Create the invitation
        $invitation = Invitation::create([
            'email' => $email,
            'colocation_id' => $colocation->id,
            'token' => $token,
            'status' => 'pending',
            'user_id' => $sender->id,
            'expires_at' => now()->addDays(7),
        ]);

        return $invitation;
    }

    /**
     * Accept an invitation to join a colocation.
     *
     * @param string $token The invitation token
     * @param User $user The user accepting the invitation
     * @return Invitation The updated invitation
     * @throws InvalidArgumentException If validation fails
     */
    public function acceptInvitation(string $token, User $user): Invitation
    {
        // Find the invitation by token
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            throw new InvalidArgumentException('Invitation non trouvée.');
        }

        // Check if invitation status is pending
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('Cette invitation a déjà été traitée.');
        }

        // Check if invitation has expired
        if ($invitation->isExpired()) {
            throw new InvalidArgumentException('Cette invitation a expiré.');
        }

        // Check if invitation email matches user email
        if (strtolower($invitation->email) !== strtolower($user->email)) {
            throw new InvalidArgumentException('Cette invitation ne vous est pas destinée.');
        }

        // Check if user already has an active colocation
        if ($this->colocationService->userHasActiveColocation($user)) {
            throw new InvalidArgumentException('Vous faites déjà partie d\'une colocation active. Vous devez quitter votre colocation actuelle avant d\'en rejoindre une autre.');
        }

        // Use DB transaction to ensure data consistency
        return DB::transaction(function () use ($invitation, $user) {
            // Add user to colocation as member
            $user->colocations()->attach($invitation->colocation_id, [
                'role' => 'member',
                'joined_at' => now(),
            ]);

            // Update invitation status
            $invitation->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);

            return $invitation->fresh();
        });
    }

    /**
     * Refuse an invitation to join a colocation.
     *
     * @param string $token The invitation token
     * @return Invitation The updated invitation
     * @throws InvalidArgumentException If validation fails
     */
    public function refuseInvitation(string $token): Invitation
    {
        // Find the invitation by token
        $invitation = Invitation::where('token', $token)->first();

        if (!$invitation) {
            throw new InvalidArgumentException('Invitation non trouvée.');
        }

        // Check if invitation status is pending
        if ($invitation->status !== 'pending') {
            throw new InvalidArgumentException('Cette invitation a déjà été traitée.');
        }

        // Update invitation status to refused
        $invitation->update([
            'status' => 'refused',
            'declined_at' => now(),
        ]);

        return $invitation;
    }

    /**
     * Get invitation by token.
     *
     * @param string $token
     * @return Invitation|null
     */
    public function getInvitationByToken(string $token): ?Invitation
    {
        return Invitation::where('token', $token)->first();
    }

    /**
     * Check if an invitation is valid for a specific user.
     *
     * @param string $token
     * @param User $user
     * @return array{valid: bool, message: string|null}
     */
    public function validateInvitationForUser(string $token, User $user): array
    {
        $invitation = $this->getInvitationByToken($token);

        if (!$invitation) {
            return ['valid' => false, 'message' => 'Invitation non trouvée.'];
        }

        if ($invitation->status !== 'pending') {
            return ['valid' => false, 'message' => 'Cette invitation a déjà été traitée.'];
        }

        if ($invitation->isExpired()) {
            return ['valid' => false, 'message' => 'Cette invitation a expiré.'];
        }

        if (strtolower($invitation->email) !== strtolower($user->email)) {
            return ['valid' => false, 'message' => 'Cette invitation ne vous est pas destinée.'];
        }

        if ($this->colocationService->userHasActiveColocation($user)) {
            return ['valid' => false, 'message' => 'Vous faites déjà partie d\'une colocation active.'];
        }

        return ['valid' => true, 'message' => null];
    }
}
