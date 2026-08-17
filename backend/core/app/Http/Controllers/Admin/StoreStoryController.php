<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StoreStory;
use App\Services\FcmService;
use Illuminate\Http\Request;

class StoreStoryController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Historias de Tiendas';

        $query = StoreStory::with('store', 'approver');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->store_id) {
            $query->where('store_id', $request->store_id);
        }

        $stories = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'pending'  => StoreStory::pending()->count(),
            'active'   => StoreStory::active()->count(),
            'approved' => StoreStory::approved()->count(),
            'rejected' => StoreStory::rejected()->count(),
            'total'    => StoreStory::count(),
        ];

        return view('admin.delivery.stories', compact('pageTitle', 'stories', 'stats'));
    }

    public function approve($id)
    {
        $story = StoreStory::pending()->findOrFail($id);

        $story->update([
            'status'      => 'active',
            'approved_by' => auth()->guard('admin')->id(),
            'approved_at' => now(),
            'starts_at'   => now(),
        ]);

        // Notify seller via push
        try {
            $seller = $story->store->seller;
            if ($seller) {
                FcmService::sendToSeller(
                    $seller,
                    'Historia aprobada',
                    'Tu historia "' . ($story->caption ?? 'Sin título') . '" ha sido aprobada y ya está activa',
                    ['type' => 'story_approved', 'story_id' => (string) $story->id]
                );
            }
        } catch (\Exception $e) {}

        $notify[] = ['success', 'Historia aprobada'];
        return back()->withNotify($notify);
    }

    public function reject(Request $request, $id)
    {
        $story = StoreStory::pending()->findOrFail($id);

        $story->update([
            'status'           => 'rejected',
            'rejection_reason' => $request->reason,
        ]);

        // Notify seller
        try {
            $seller = $story->store->seller;
            if ($seller) {
                FcmService::sendToSeller(
                    $seller,
                    'Historia rechazada',
                    'Tu historia fue rechazada: ' . ($request->reason ?? 'No cumple los requisitos'),
                    ['type' => 'story_rejected', 'story_id' => (string) $story->id]
                );
            }
        } catch (\Exception $e) {}

        $notify[] = ['success', 'Historia rechazada'];
        return back()->withNotify($notify);
    }

    public function destroy($id)
    {
        $story = StoreStory::findOrFail($id);

        $filePath = public_path('assets/images/store_stories/' . $story->media_path);
        if (file_exists($filePath)) unlink($filePath);
        $story->views()->delete();
        $story->delete();

        $notify[] = ['success', 'Historia eliminada'];
        return back()->withNotify($notify);
    }
}
