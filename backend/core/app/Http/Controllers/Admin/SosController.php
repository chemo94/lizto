<?php
namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\SosAlert;
use Illuminate\Http\Request;

class SosController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = __('SOS Alerts');

        $query = SosAlert::with(['ride.user', 'ride.driver']);

        if ($request->filled('status')) {
            $query->where('status', (int) $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('ride', function ($q) use ($search) {
                $q->where('uid', 'like', "%$search%")
                    ->orWhereHas('user', function ($qq) use ($search) {
                        $qq->where('username', 'like', "%$search%");
                    });
            });
        }

        $alerts = $query->orderBy('id', 'desc')->paginate(getPaginate());

        return view('admin.sos.list', compact('pageTitle', 'alerts'));
    }

    public function location($id)
    {
        $pageTitle = __('SOS Location');
        $alert = SosAlert::with(['ride.user', 'ride.driver'])->findOrFail($id);
        return view('admin.sos.location', compact('pageTitle', 'alert'));
    }

    public function resolve($id)
    {
        $alert = SosAlert::findOrFail($id);
        $alert->status = Status::DISABLE;
        $alert->save();
        $notify[] = ['success', __('SOS alert resolved successfully')];
        return back()->withNotify($notify);
    }
}
