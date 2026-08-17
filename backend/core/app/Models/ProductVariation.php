<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class ProductVariation extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'double',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
