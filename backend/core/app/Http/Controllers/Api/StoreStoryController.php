<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreStory;
use App\Models\StoryView;
use Illuminate\Http\Request;

class StoreStoryController extends Controller
{
    public function index(Request $request)
    {
        $storeIds = Store::active()->pluck('id');
        $userId   = $request->user() ? $request->user()->id : 0;

        $storyTitles = StoreStory::visible()
            ->whereIn('store_id', $storeIds)
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy('store_id');

        $storesWithStories = [];
        foreach ($storyTitles as $storeId => $stories) {
            $store = $stories->first()->store;
            $unseenStories = $stories->filter(function ($s) use ($userId) {
                return !$s->views()->where('user_id', $userId)->exists();
            });

            $storesWithStories[] = [
                'store_id'      => (int) $storeId,
                'store_name'    => $store->name,
                'store_image'   => $store->image,
                'stories_count' => $stories->count(),
                'unseen_count'  => $unseenStories->count(),
                'preview_image' => $stories->first()->media_path,
            ];
        }

        return apiResponse('stories', 'success', ['Historias de tiendas'], [
            'stores'          => $storesWithStories,
            'story_base_path' => 'assets/images/store_stories/',
        ]);
    }

    public function byStore(Request $request, $storeId)
    {
        $store = Store::active()->findOrFail($storeId);
        $userId = $request->user() ? $request->user()->id : 0;

        $stories = StoreStory::visible()
            ->where('store_id', $store->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($s) use ($userId) {
                $seen = $userId ? $s->views()->where('user_id', $userId)->exists() : false;
                return [
                    'id'          => $s->id,
                    'media_type'  => $s->media_type,
                    'media_url'   => $s->media_full_url,
                    'caption'     => $s->caption,
                    'duration'    => $s->duration,
                    'seen'        => $seen,
                    'created_at'  => $s->created_at->toISOString(),
                ];
            });

        return apiResponse('store_stories', 'success', ['Historias de ' . $store->name], [
            'store_id'   => $store->id,
            'store_name' => $store->name,
            'stories'    => $stories,
        ]);
    }

    public function view(Request $request, $storyId)
    {
        $story = StoreStory::visible()->findOrFail($storyId);
        $userId = $request->user() ? $request->user()->id : null;

        // Track view (unique per user or unique per IP for guest)
        if ($userId) {
            $existing = StoryView::where('store_story_id', $story->id)
                ->where('user_id', $userId)
                ->first();
        } else {
            $existing = StoryView::where('store_story_id', $story->id)
                ->whereNull('user_id')
                ->where('ip_address', $request->ip())
                ->first();
        }

        if (!$existing) {
            StoryView::create([
                'store_story_id' => $story->id,
                'user_id'        => $userId,
                'ip_address'     => $request->ip(),
            ]);

            $story->increment('consumed_impressions');

            // Auto-pause if budget exhausted
            if ($story->isExpired() && $story->status === 'active') {
                $story->update(['status' => 'completed']);
            }
        }

        return apiResponse('story_viewed', 'success', ['Vista registrada']);
    }
}
