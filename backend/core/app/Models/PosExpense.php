<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosExpense extends Model {
    protected $guarded = ['id'];
    protected $table = 'pos_expenses';
    protected $casts = ['amount'=>'double','expense_date'=>'datetime'];
    public function seller(){ return $this->belongsTo(Seller::class); }
    public function session(){ return $this->belongsTo(PosCashSession::class, 'cash_session_id'); }
}
