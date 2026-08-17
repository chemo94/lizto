<?php namespace App\Models; use Illuminate\Database\Eloquent\Model;
class PosArea extends Model {
    protected $guarded = ['id']; protected $table = 'pos_areas';
    public function seller(){return $this->belongsTo(Seller::class);}
    public function tables(){return $this->hasMany(PosTable::class, 'pos_area_id')->orderBy('name');}
}
