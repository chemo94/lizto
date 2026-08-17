<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class StoreCategory extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = ['status' => 'integer', 'sort_order' => 'integer'];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class)->active()->orderBy('sort_order');
    }
}
