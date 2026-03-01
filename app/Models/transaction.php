<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Expense;
use App\Models\Colocation;

class transaction extends Model
{
    protected $fillable = [
        'debtor_id',
        'creditor_id',
        'colocation_id',
        'expense_id',
        'amount',
        'status',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }

    public function debtor(){
        return $this->belongsTo(User::class , 'debtor_id');
    }

    public function creditor(){
        return $this->belongsTo(User::class , 'creditor_id');
    }
    public function expense(){
        return $this->belongsTo(Expense::class);
    }
    public function colocation(){
        return $this->belongsTo( Colocation::class);
    }
}
