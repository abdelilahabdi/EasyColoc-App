<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invitation extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'colocation_id',
        'token',
        'status',
        'user_id',
        'accepted_at',
        'declined_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * The colocation that belongs to the invitation.
     */
    public function colocation(): BelongsTo
    {
        return $this->belongsTo(Colocation::class);
    }

    /**
     * The user who sent the invitation.
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Check if the invitation is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Accept the invitation and add the user to the colocation.
     */
    public function accept(): void
    {
        $user = User::where('email', $this->email)->first();

        if ($user) {
            // Add user to colocation as member
            $user->colocations()->attach($this->colocation_id, ['role' => 'member']);

            // Update invitation status
            $this->update([
                'status' => 'accepted',
                'accepted_at' => now(),
            ]);
        }
    }

    /**
     * Decline the invitation.
     */
    public function decline(): void
    {
        $this->update([
            'status' => 'refused',
            'declined_at' => now(),
        ]);
    }

    /**
     * Check if the invitation has expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
