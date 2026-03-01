<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_admin',
        'is_banned',
        'reputation',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_banned' => 'boolean',
        ];
    }

    /**
     * The colocations that belong to the user.
     */
    public function colocations(): BelongsToMany
    {
        return $this->belongsToMany(Colocation::class, 'colocation_user')
            ->withPivot('role', 'joined_at', 'left_at')
            ->withTimestamps();
    }

    /**
     * The colocations owned by the user.
     */
    public function ownedColocations(): HasMany
    {
        return $this->hasMany(Colocation::class, 'owner_id');
    }

    /**
     * The expenses that belong to the user (as payer).
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'payer_id');
    }

    /**
     * The settlements where the user is the sender (payer).
     */
    public function settlementsSent(): HasMany
    {
        return $this->hasMany(Settlement::class, 'sender_id');
    }

    /**
     * The settlements where the user is the receiver (recipient).
     */
    public function settlementsReceived(): HasMany
    {
        return $this->hasMany(Settlement::class, 'receiver_id');
    }

    /**
     * The payments where the user is the sender (payer).
     */
    public function paymentsSent(): HasMany
    {
        return $this->hasMany(Payment::class, 'from_user_id');
    }

    /**
     * The payments where the user is the receiver (recipient).
     */
    public function paymentsReceived(): HasMany
    {
        return $this->hasMany(Payment::class, 'to_user_id');
    }

    public function transactions (){

        return $this->HasMany(transaction::class);
    }

    /**
     * Check if the user is a global admin.
     */
    public function isGlobalAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Check if the user is an admin (is_admin flag).
     */
    public function isAdmin(): bool
    {
        return $this->is_admin === true;
    }

    /**
     * Check if the user is banned.
     */
    public function isBanned(): bool
    {
        return $this->is_banned === true;
    }

    /**
     * Ban the user.
     */
    public function ban(): bool
    {
        return $this->update(['is_banned' => true]);
    }

    /**
     * Unban the user.
     */
    public function unban(): bool
    {
        return $this->update(['is_banned' => false]);
    }

    /**
     * Scope to get only admins.
     */
    public function scopeAdmins($query)
    {
        return $query->where('is_admin', true);
    }

    /**
     * Scope to get only banned users.
     */
    public function scopeBanned($query)
    {
        return $query->where('is_banned', true);
    }
}
