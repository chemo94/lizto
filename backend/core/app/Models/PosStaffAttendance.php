<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosStaffAttendance extends Model
{
    protected $guarded = ['id'];
    protected $table = 'pos_staff_attendance';

    protected $casts = [
        'date'      => 'date',
        'clock_in'  => 'datetime',
        'clock_out' => 'datetime',
        'hours_worked' => 'double'
    ];

    public function staff()
    {
        return $this->belongsTo(PosStaff::class, 'pos_staff_id');
    }
}
