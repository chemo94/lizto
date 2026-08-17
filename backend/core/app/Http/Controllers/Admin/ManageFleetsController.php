<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Fleet;
use Illuminate\Http\Request;

class ManageFleetsController extends Controller
{
    public function index()
    {
        $pageTitle = 'Todas las Flotas';
        $fleets    = Fleet::with('owner')->latest()->paginate(getPaginate());
        return view('admin.fleet.index', compact('pageTitle', 'fleets'));
    }

    public function detail($id)
    {
        $pageTitle = 'Detalle de la Flota';
        $fleet     = Fleet::with(['owner.wallet.transactions', 'drivers'])->findOrFail($id);

        if ($fleet->owner && !$fleet->owner->wallet) {
            \App\Models\Wallet::create([
                'holder_type' => get_class($fleet->owner),
                'holder_id'   => $fleet->owner->id,
            ]);
            $fleet->load('owner.wallet.transactions');
        }

        $transactions = $fleet->owner->wallet ? $fleet->owner->wallet->transactions : collect();
        $zones        = \App\Models\Zone::active()->orderBy('name')->get();
        return view('admin.fleet.detail', compact('pageTitle', 'fleet', 'transactions', 'zones'));
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name'                   => 'required|string|max:100',
            'email'                  => 'required|email|max:100',
            'lizto_commission_rate'  => 'required|numeric|min:0|max:100',
            'driver_commission_rate' => 'required|numeric|min:0|max:100',
            'zone_id'                => 'nullable|exists:zones,id',
            'commission_type'        => 'required|in:percentage,subscription',
            'status'                 => 'required|in:0,1',
        ]);

        $fleet = Fleet::findOrFail($id);
        $fleet->update([
            'name'                   => $request->name,
            'email'                  => $request->email,
            'lizto_commission_rate'  => $request->lizto_commission_rate,
            'driver_commission_rate' => $request->driver_commission_rate,
            'zone_id'                => $request->zone_id,
            'commission_type'        => $request->commission_type,
            'status'                 => $request->status,
        ]);

        return back()->withNotify([['success', 'Configuración de la flota actualizada con éxito.']]);
    }

    public function adjustBalance(Request $request, $id)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'type'   => 'required|in:1,2', // 1 = Add, 2 = Subtract
            'remark' => 'nullable|string|max:255',
        ]);

        $fleet = Fleet::with('owner.wallet')->findOrFail($id);
        $owner = $fleet->owner;

        if (!$owner) {
            return back()->withNotify([['error', 'El dueño de la flota no existe.']]);
        }

        // Ensure wallet exists
        $wallet = $owner->wallet;
        if (!$wallet) {
            $wallet = \App\Models\Wallet::create([
                'holder_type' => get_class($owner),
                'holder_id'   => $owner->id,
            ]);
        }

        if ($request->type == 1) {
            $wallet->credit($request->amount, 'admin_adjust', $request->remark ?? 'Ajuste manual de saldo (Crédito)');
            $notify[] = ['success', 'Saldo agregado correctamente.'];
        } else {
            $wallet->debit($request->amount, 'commission', $request->remark ?? 'Ajuste manual de saldo (Débito)');
            $notify[] = ['success', 'Saldo debitado correctamente.'];
        }

        return back()->withNotify($notify);
    }
}
