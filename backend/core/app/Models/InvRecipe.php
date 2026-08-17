<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvRecipe extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_recipes';
    protected $casts   = ['portions' => 'double'];

    const TYPE_KITCHEN = 'kitchen';
    const TYPE_BAR     = 'bar';

    public function seller()  { return $this->belongsTo(Seller::class); }
    public function product() { return $this->belongsTo(Product::class); }

    public function items()
    {
        return $this->hasMany(InvRecipeItem::class, 'recipe_id');
    }

    public function productions()
    {
        return $this->hasMany(InvProduction::class, 'recipe_id');
    }

    public function isBar(): bool { return $this->recipe_type === self::TYPE_BAR; }

    public function scopeActive($q) { return $q->where('status', 'active'); }
    public function scopeKitchen($q) { return $q->where('recipe_type', self::TYPE_KITCHEN); }
    public function scopeBar($q)     { return $q->where('recipe_type', self::TYPE_BAR); }
}
