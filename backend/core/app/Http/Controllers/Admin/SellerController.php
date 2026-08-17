<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Seller;
use App\Models\Store;
use App\Services\DeliveryFinancialLedger;
use Illuminate\Http\Request;

class SellerController extends Controller
{
    public function index()
    {
        $pageTitle = 'Todos los Vendedores';
        $sellers   = Seller::withCount('stores')->latest()->paginate(getPaginate());
        return view('admin.seller.index', compact('pageTitle', 'sellers'));
    }

    public function pending()
    {
        $pageTitle = 'Vendedores Pendientes';
        $sellers   = Seller::where('is_verified', 0)->withCount('stores')->latest()->paginate(getPaginate());
        return view('admin.seller.index', compact('pageTitle', 'sellers'));
    }

    public function approved()
    {
        $pageTitle = 'Vendedores Aprobados';
        $sellers   = Seller::where('is_verified', 1)->where('status', 1)->withCount('stores')->latest()->paginate(getPaginate());
        return view('admin.seller.index', compact('pageTitle', 'sellers'));
    }

    public function banned()
    {
        $pageTitle = 'Vendedores Bloqueados';
        $sellers   = Seller::where('status', 0)->withCount('stores')->latest()->paginate(getPaginate());
        return view('admin.seller.index', compact('pageTitle', 'sellers'));
    }

    public function status(Request $request, $id)
    {
        $seller = Seller::findOrFail($id);
        $seller->status = !$seller->status;
        $seller->save();

        $notify[] = ['success', 'Estado del vendedor actualizado'];
        return back()->withNotify($notify);
    }

    public function verification(Request $request, $id)
    {
        $seller = Seller::findOrFail($id);
        $seller->is_verified = !$seller->is_verified;
        $seller->save();

        $notify[] = ['success', 'Verificación del vendedor actualizada'];
        return back()->withNotify($notify);
    }

    public function detail($id)
    {
        $pageTitle = 'Detalle del Vendedor';
        $seller    = Seller::with('stores.products', 'wallet')->findOrFail($id);
        $receivableTransactions = $seller->receivableTransactions()->with('admin')->limit(30)->get();
        return view('admin.seller.detail', compact('pageTitle', 'seller', 'receivableTransactions'));
    }

    public function saveStoreQr(Request $request, $storeId)
    {
        $data = $request->validate([
            'yape_qr_string' => 'nullable|string|max:4096',
            'plin_qr_string' => 'nullable|string|max:4096',
        ]);

        Store::findOrFail($storeId)->update([
            'yape_qr_string' => filled($data['yape_qr_string'] ?? null) ? trim($data['yape_qr_string']) : null,
            'plin_qr_string' => filled($data['plin_qr_string'] ?? null) ? trim($data['plin_qr_string']) : null,
        ]);

        return back()->withNotify([['success', 'Códigos QR de pago actualizados']]);
    }

    public function settleReceivable(Request $request, $id)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'settlement_method' => 'required|in:balance,bank,yape,plin',
            'reference' => 'required_unless:settlement_method,balance|nullable|string|max:150',
            'notes' => 'nullable|string|max:500',
        ]);

        $seller = Seller::findOrFail($id);
        DeliveryFinancialLedger::settleSeller(
            $seller,
            (float) $data['amount'],
            $data['settlement_method'],
            auth('admin')->id(),
            $data['reference'] ?? null,
            $data['notes'] ?? null
        );

        return back()->withNotify([['success', 'Liquidación del seller registrada correctamente']]);
    }
}
