<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Complaint;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ComplaintController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = __('Libro de Reclamaciones');
        $query = Complaint::query();

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('ticket_number', 'LIKE', "%$search%")
                  ->orWhere('full_name', 'LIKE', "%$search%")
                  ->orWhere('document_number', 'LIKE', "%$search%");
            });
        }

        if ($request->status != null) {
            $query->where('status', $request->status);
        }

        $complaints = $query->latest()->paginate(getPaginate());
        return view('admin.complaints.index', compact('pageTitle', 'complaints'));
    }

    public function details($id)
    {
        $complaint = Complaint::findOrFail($id);
        $pageTitle = __('Detalles de Reclamación') . ' - ' . $complaint->ticket_number;
        return view('admin.complaints.details', compact('pageTitle', 'complaint'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'status'           => 'required|in:1,2,3',
            'provider_actions' => 'required|string|max:5000',
        ]);

        $complaint = Complaint::findOrFail($id);
        $complaint->status = $request->status;
        $complaint->provider_actions = $request->provider_actions;
        $complaint->responded_at = Carbon::now();
        $complaint->save();

        // Notify Claimant via Email
        try {
            notify([
                'email' => $complaint->email,
                'username' => $complaint->full_name,
                'fullname' => $complaint->full_name,
            ], 'DEFAULT', [
                'subject' => 'Actualización de Reclamación: ' . $complaint->ticket_number,
                'message' => "Estimado(a) {$complaint->full_name},<br><br>Su reclamación registrada con el ticket <b>{$complaint->ticket_number}</b> ha sido actualizada al estado: <b>" . $complaint->status_name . "</b>.<br><br><b>Respuesta del proveedor:</b><br>{$complaint->provider_actions}<br><br>Atentamente,<br>" . gs('site_name')
            ]);
        } catch (\Exception $e) {
            // Silence mail exceptions if template/config is missing
        }

        $notify[] = ['success', 'Reclamación actualizada y notificada con éxito'];
        return redirect()->route('admin.complaints.details', $complaint->id)->withNotify($notify);
    }
}
