<?php

namespace App\Http\Controllers;

use App\Models\DeliveryOrder;
use App\Models\WalletTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserPanelController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'check.status']);
    }

    public function dashboard()
    {
        $user = Auth::user();
        $pageTitle = 'Mi Panel';

        $recentOrders = DeliveryOrder::where('user_id', $user->id)
            ->with('store')
            ->latest()
            ->limit(5)
            ->get();

        $orderStats = [
            'total'    => DeliveryOrder::where('user_id', $user->id)->count(),
            'pending'  => DeliveryOrder::where('user_id', $user->id)->where('status', 'pending')->count(),
            'active'   => DeliveryOrder::where('user_id', $user->id)->whereIn('status', ['confirmed', 'preparing', 'on_the_way'])->count(),
            'completed'=> DeliveryOrder::where('user_id', $user->id)->where('status', 'delivered')->count(),
        ];

        $wallet = $user->wallet;
        $walletBalance = $wallet ? $wallet->balance : 0;

        return view('templates.basic.user.dashboard', compact('pageTitle', 'user', 'recentOrders', 'orderStats', 'walletBalance'));
    }

    public function profile()
    {
        $user = Auth::user();
        $pageTitle = 'Mi Perfil';
        return view('templates.basic.user.profile', compact('pageTitle', 'user'));
    }

    public function profileUpdate(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'firstname' => 'required|string|max:100',
            'lastname'  => 'required|string|max:100',
            'email'     => 'required|email|max:100|unique:users,email,' . $user->id,
            'mobile'    => 'nullable|string|max:20',
        ]);

        $user->update($request->only(['firstname', 'lastname', 'email', 'mobile']));

        return back()->with('success', 'Perfil actualizado correctamente.');
    }

    public function wallet()
    {
        $user = Auth::user();
        $pageTitle = 'Billetera';

        $wallet = $user->wallet;
        $walletBalance = $wallet ? $wallet->balance : 0;

        $transactions = WalletTransaction::whereHas('wallet', function ($q) use ($user) {
            $q->where('holder_type', get_class($user))
              ->where('holder_id', $user->id);
        })->latest()->paginate(15);

        return view('templates.basic.user.wallet', compact('pageTitle', 'user', 'walletBalance', 'transactions'));
    }

    public function orderDetail($id)
    {
        $user = Auth::user();
        $pageTitle = 'Detalle del Pedido';

        $order = DeliveryOrder::where('user_id', $user->id)
            ->with('store', 'driver', 'items.variation', 'items.addons')
            ->findOrFail($id);

        return view('templates.basic.user.order_detail', compact('pageTitle', 'user', 'order'));
    }

    public function driverLocation($id)
    {
        $user = Auth::user();
        $order = DeliveryOrder::where('user_id', $user->id)->findOrFail($id);

        if (!$order->driver_id) {
            return response()->json(['lat' => null, 'lng' => null]);
        }

        $driver = $order->driver;
        return response()->json([
            'lat' => $driver->current_lat ?? null,
            'lng' => $driver->current_lot ?? null,
        ]);
    }
}
