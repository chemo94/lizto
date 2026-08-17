<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;

class PosRegister extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'pos_registers';
    protected $casts   = ['is_active' => 'boolean'];

    public function seller()
    {
        return $this->belongsTo(Seller::class);
    }

    public function cashSessions()
    {
        return $this->hasMany(PosCashSession::class, 'pos_register_id');
    }

    public function staff()
    {
        return $this->hasMany(PosStaff::class, 'pos_register_id');
    }

    public function openSession()
    {
        return $this->hasOne(PosCashSession::class, 'pos_register_id')->where('status', 'open');
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true);
    }
}
