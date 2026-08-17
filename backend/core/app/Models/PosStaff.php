<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class PosStaff extends Model
{
    use HasApiTokens;
    protected $guarded = ['id'];
    protected $table = 'pos_staff';

    protected $casts = [
        'commission_rate' => 'double',
        'base_salary'      => 'double',
        'hire_date'        => 'date',
        'permissions'      => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(SellerCompany::class, 'seller_company_id');
    }

    public function hasPermission($permission): bool
    {
        if (empty($this->permissions)) {
            return false;
        }
        return in_array($permission, $this->permissions);
    }

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function register()
    {
        return $this->belongsTo(PosRegister::class, 'pos_register_id');
    }

    public function orders()
    {
        return $this->hasMany(PosOrder::class, 'pos_staff_id');
    }

    public function attendances()
    {
        return $this->hasMany(PosStaffAttendance::class, 'pos_staff_id');
    }

    public function payrolls()
    {
        return $this->hasMany(PosStaffPayroll::class, 'pos_staff_id');
    }
}
