<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosBankAccount extends Model
{
    protected $guarded = ['id'];
    protected $table = 'pos_bank_accounts';

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function transactions()
    {
        return $this->hasMany(PosTransaction::class, 'pos_bank_account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    // Dynamic balance calculation
    public function getBalanceAttribute()
    {
        $inflow = $this->transactions()
            ->whereIn('type', ['sale', 'cash_in'])
            ->sum('amount');

        $outflow = $this->transactions()
            ->where('type', 'cash_out')
            ->sum('amount');

        return (double) ($inflow - $outflow);
    }
}
