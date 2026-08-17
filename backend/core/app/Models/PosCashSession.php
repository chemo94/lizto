<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosCashSession extends Model {
    protected $guarded = ['id'];
    protected $table = 'pos_cash_sessions';
    protected $casts = ['opening_balance'=>'double','closing_balance'=>'double','total_sales'=>'double','total_expenses'=>'double','total_cash_in'=>'double','total_cash_out'=>'double','opened_at'=>'datetime','closed_at'=>'datetime'];
    public function seller(){ return $this->belongsTo(Seller::class); }
    public function register(){ return $this->belongsTo(PosRegister::class, 'pos_register_id'); }
    public function transactions(){ return $this->hasMany(PosTransaction::class, 'cash_session_id'); }
    public function expenses(){ return $this->hasMany(PosExpense::class, 'cash_session_id'); }
    public function scopeOpen($q){ return $q->where('status','open'); }
}
