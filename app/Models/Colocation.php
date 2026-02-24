<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Colocation extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'status',
    ];

    /**
     * The users that belong to the colocation.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'colocation_user')
            ->withPivot('role', 'left_at')
            ->withTimestamps();
    }

    /**
     * The expenses that belong to the colocation.
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class);
    }
}
