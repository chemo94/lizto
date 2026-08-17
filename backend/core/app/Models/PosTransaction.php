<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosTransaction extends Model {
    protected $guarded = ['id'];
    protected $table = 'pos_transactions';
    protected $casts = ['amount'=>'double'];
    public function session(){ return $this->belongsTo(PosCashSession::class, 'cash_session_id'); }
    public function seller(){ return $this->belongsTo(Seller::class); }
    public function order(){ return $this->belongsTo(PosOrder::class, 'pos_order_id'); }
    public function bankAccount(){ return $this->belongsTo(PosBankAccount::class, 'pos_bank_account_id'); }
}
