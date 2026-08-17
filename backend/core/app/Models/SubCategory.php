<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = ['status' => 'integer', 'sort_order' => 'integer'];

    public function generalCategory()
    {
        return $this->belongsTo(GeneralCategory::class);
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_sub_category')->active()->orderBy('name');
    }
}
