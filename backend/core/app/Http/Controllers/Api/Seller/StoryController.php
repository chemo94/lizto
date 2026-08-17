<?php

namespace App\Http\Controllers\Api\Seller;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\StoreStory;
use App\Services\FcmService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class StoryController extends Controller
{
    public function index(Request $request)
    {
        $seller = auth()->user();
        $storeIds = Store::where('seller_id', $seller->id)->pluck('id');

        $stories = StoreStory::whereIn('store_id', $storeIds)
            ->with('store:id,name')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($s) => $this->formatStory($s));

        $summary = [
            'active_stories'   => $stories->where('status', 'active')->count(),
            'pending_stories'  => $stories->where('status', 'pending')->count(),
            'total_impressions'=> $stories->sum('consumed_impressions'),
            'total_spent'      => round($stories->sum(function($s) { return $s['consumed_impressions'] * 0.005; }), 2),
        ];

        return apiResponse('stories', 'success', ['Historias'], [
            'stories'    => $stories,
            'summary'    => $summary,
            'media_path' => 'assets/images/store_stories/',
        ]);
    }

    public function show($id)
    {
        $seller = auth()->user();
        $storeIds = Store::where('seller_id', $seller->id)->pluck('id');
        $story = StoreStory::whereIn('store_id', $storeIds)->findOrFail($id);

        return apiResponse('story_detail', 'success', ['Detalle'], [
            'story' => $this->formatStory($story),
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'store_id'   => 'required|exists:stores,id',
            'media_type' => 'required|in:image,video',
            'media'      => 'required|file|mimes:jpg,jpeg,png,webp,mp4,mov,avi|max:51200',
            'caption'    => 'nullable|string|max:255',
            'duration'   => 'nullable|integer|min:1|max:60',
            'budget'     => 'required|numeric|min:0.50|max:9999',
            'hours'      => 'required|integer|min:1|max:720',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        $seller  = auth()->user();
        $store   = Store::where('seller_id', $seller->id)->findOrFail($request->store_id);
        $budget  = (float) $request->budget;
        $hours   = (int) $request->hours;
        $cpi     = 0.005;

        $totalImpressions = (int) floor($budget / $cpi);

        $mediaPath = 'assets/images/store_stories';
        $filename  = uniqid() . time() . '.' . $request->file('media')->getClientOriginalExtension();
        $request->file('media')->move(public_path($mediaPath), $filename);

        $duration = $request->duration ?? ($request->media_type === 'image' ? 5 : 10);

        $story = StoreStory::create([
            'store_id'      => $store->id,
            'media_type'    => $request->media_type,
            'media_path'    => $filename,
            'caption'       => $request->caption,
            'duration'      => $duration,
            'budget'        => $budget,
            'cpi'           => $cpi,
            'total_impressions'=> $totalImpressions,
            'consumed_impressions' => 0,
            'status'        => 'active',
            'starts_at'     => now(),
            'ends_at'       => now()->addHours($hours),
        ]);

        return apiResponse('story_created', 'success', ['Historia creada exitosamente y ya se encuentra activa.'], [
            'story' => $this->formatStory($story),
        ]);
    }

    public function destroy($id)
    {
        $seller = auth()->user();
        $storeIds = Store::where('seller_id', $seller->id)->pluck('id');
        $story = StoreStory::whereIn('store_id', $storeIds)
            ->whereIn('status', ['pending', 'approved', 'paused', 'completed'])
            ->findOrFail($id);

        $filePath = public_path('assets/images/store_stories/' . $story->media_path);
        if (file_exists($filePath)) unlink($filePath);

        $story->views()->delete();
        $story->delete();

        return apiResponse('story_deleted', 'success', ['Historia eliminada']);
    }

    public function pause($id)
    {
        $seller = auth()->user();
        $storeIds = Store::where('seller_id', $seller->id)->pluck('id');
        $story = StoreStory::whereIn('store_id', $storeIds)->where('status', 'active')->findOrFail($id);
        $story->update(['status' => 'paused']);

        return apiResponse('story_paused', 'success', ['Historia pausada']);
    }

    public function resume($id)
    {
        $seller = auth()->user();
        $storeIds = Store::where('seller_id', $seller->id)->pluck('id');
        $story = StoreStory::whereIn('store_id', $storeIds)->where('status', 'paused')->findOrFail($id);
        $story->update(['status' => 'active']);

        return apiResponse('story_resumed', 'success', ['Historia reanudada']);
    }

    private function formatStory($s)
    {
        return [
            'id'                    => $s->id,
            'store_id'              => $s->store_id,
            'store_name'            => $s->store->name ?? '',
            'media_type'            => $s->media_type,
            'media_url'             => $s->media_full_url,
            'caption'               => $s->caption,
            'duration'              => $s->duration,
            'budget'                => $s->budget,
            'cpi'                   => $s->cpi,
            'total_impressions'     => $s->total_impressions,
            'consumed_impressions'  => $s->consumed_impressions,
            'remaining_impressions' => $s->remaining_impressions,
            'remaining_budget'      => $s->remaining_budget,
            'status'                => $s->status,
            'starts_at'             => $s->starts_at,
            'ends_at'               => $s->ends_at,
            'created_at'            => $s->created_at,
        ];
    }
}
