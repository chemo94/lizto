<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosStaffPayroll extends Model
{
    protected $guarded = ['id'];
    protected $table = 'pos_staff_payroll';

    protected $casts = [
        'period_start'        => 'date',
        'period_end'          => 'date',
        'base_salary_earned'  => 'double',
        'commissions_earned'  => 'double',
        'bonuses'             => 'double',
        'deductions'          => 'double',
        'net_salary'          => 'double',
        'payment_date'        => 'date'
    ];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function staff()
    {
        return $this->belongsTo(PosStaff::class, 'pos_staff_id');
    }
}
