<?php

namespace App\Models;

use App\Traits\GlobalStatus;
use Illuminate\Database\Eloquent\Model;

class GeneralCategory extends Model
{
    use GlobalStatus;

    protected $guarded = ['id'];

    protected $casts = ['status' => 'integer', 'sort_order' => 'integer'];

    public function subCategories()
    {
        return $this->hasMany(SubCategory::class)->active()->orderBy('sort_order');
    }

    public function allSubCategories()
    {
        return $this->hasMany(SubCategory::class)->orderBy('sort_order');
    }

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_general_category');
    }
}
