<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvProduction extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_productions';
    protected $casts   = ['produced_at' => 'datetime'];

    public function seller()      { return $this->belongsTo(Seller::class); }
    public function recipe()      { return $this->belongsTo(InvRecipe::class, 'recipe_id'); }
    public function product()     { return $this->belongsTo(Product::class); }
    public function cashSession() { return $this->belongsTo(PosCashSession::class, 'cash_session_id'); }

    public function scopeCompleted($q) { return $q->where('status', 'completed'); }
    public function scopeVoided($q)    { return $q->where('status', 'voided'); }
}
