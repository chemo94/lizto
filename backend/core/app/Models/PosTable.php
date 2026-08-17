<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PosTable extends Model
{
    protected $guarded = ['id'];

    public function seller() { return $this->belongsTo(Seller::class); }
    public function store()  { return $this->belongsTo(Store::class); }
    public function orders() { return $this->hasMany(PosOrder::class, 'pos_table_id'); }
    public function linkedTo() { return $this->belongsTo(PosTable::class, 'linked_to_table_id'); }
    public function linkedTables() { return $this->hasMany(PosTable::class, 'linked_to_table_id'); }
    public function area() { return $this->belongsTo(PosArea::class, 'pos_area_id'); }
}
