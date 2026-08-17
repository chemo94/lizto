<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoryView extends Model
{
    protected $guarded = ['id'];

    protected $casts = [
        'store_story_id' => 'integer',
        'user_id'        => 'integer',
    ];

    public function story()
    {
        return $this->belongsTo(StoreStory::class, 'store_story_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
