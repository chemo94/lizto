<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvRecipeItem extends Model
{
    protected $guarded = ['id'];
    protected $table   = 'inv_recipe_items';
    protected $casts   = [
        'quantity_gross' => 'double',
        'waste_pct'      => 'double',
        'quantity_net'   => 'double',
    ];

    public function recipe() { return $this->belongsTo(InvRecipe::class, 'recipe_id'); }
    public function item()   { return $this->belongsTo(InvItem::class, 'item_id'); }

    /**
     * Recalculate net quantity from gross and waste%.
     */
    public function calculateNet(): float
    {
        return round($this->quantity_gross * (1 - $this->waste_pct / 100), 8);
    }

    /**
     * How much of this ingredient is needed for N portions.
     */
    public function neededFor(int|float $portions): float
    {
        return round($this->quantity_net * $portions, 8);
    }
}
