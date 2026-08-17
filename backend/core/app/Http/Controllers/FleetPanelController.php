<?php

namespace App\Http\Controllers;

use App\Models\Fleet;
use App\Models\FleetOwner;
use App\Models\Driver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FleetPanelController extends Controller
{
    public function showLogin()
    {
        if (Auth::guard('fleet_owner')->check()) {
            return redirect()->route('fleet.dashboard');
        }
        $pageTitle = 'Login de Flota';
        return view('fleet.login', compact('pageTitle'));
    }

    public function showRegister()
    {
        if (Auth::guard('fleet_owner')->check()) {
            return redirect()->route('fleet.dashboard');
        }
        $pageTitle = 'Registro de Dueño de Flota';
        $zones = \App\Models\Zone::active()->orderBy('name')->get();
        return view('fleet.register', compact('pageTitle', 'zones'));
    }

    public function register(Request $request)
    {
        $request->validate([
            'firstname' => 'required|string|max:40',
            'lastname'  => 'required|string|max:40',
            'username'  => 'required|string|unique:fleet_owners,username|max:40',
            'email'     => 'required|string|email|unique:fleet_owners,email|max:40',
            'phone'     => 'required|string|max:40',
            'password'  => 'required|string|min:6|confirmed',
            'fleet_name'=> 'required|string|max:100',
            'zone_id'   => 'required|exists:zones,id',
        ]);

        $owner = FleetOwner::create([
            'firstname' => $request->firstname,
            'lastname'  => $request->lastname,
            'username'  => $request->username,
            'email'     => $request->email,
            'phone'     => $request->phone,
            'password'  => Hash::make($request->password),
        ]);

        Fleet::create([
            'name'             => $request->fleet_name,
            'email'            => $request->email,
            'owner_id'         => $owner->id,
            'zone_id'          => $request->zone_id,
            'commission_type'  => 'subscription', // defaults to Plan Lizto Partner
            'lizto_commission_rate' => 0.00,       // Lizto does not retain percentage
            'invite_code'      => 'FLOT-' . strtoupper(getTrx(6)),
        ]);

        if (!$owner->wallet) {
            \App\Models\Wallet::create([
                'holder_type' => get_class($owner),
                'holder_id'   => $owner->id,
            ]);
        }

        Auth::guard('fleet_owner')->login($owner);

        return redirect()->route('fleet.dashboard')->withNotify([['success', 'Registro exitoso. Tu flota ya está creada.']]);
    }

    public function webLogin(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string',
        ]);

        if (Auth::guard('fleet_owner')->attempt($request->only('email', 'password'))) {
            // Ensure they have a Fleet record
            $owner = Auth::guard('fleet_owner')->user();
            if (!$owner->fleet) {
                Fleet::create([
                    'name' => $owner->firstname . ' ' . $owner->lastname . ' Fleet',
                    'email' => $owner->email,
                    'owner_id' => $owner->id,
                    'invite_code' => 'FLOT-' . strtoupper(getTrx(6)),
                ]);
            }
            if (!$owner->wallet) {
                \App\Models\Wallet::create([
                    'holder_type' => get_class($owner),
                    'holder_id'   => $owner->id,
                ]);
            }
            return redirect()->route('fleet.dashboard');
        }

        return back()->withErrors(['email' => 'Credenciales incorrectas']);
    }

    public function webLogout()
    {
        Auth::guard('fleet_owner')->logout();
        return redirect()->route('fleet.login');
    }

    private function getFleet()
    {
        return Auth::guard('fleet_owner')->user()->fleet;
    }

    public function dashboard()
    {
        $pageTitle = 'Panel de Control - Flota';
        $fleet = $this->getFleet();
        $driversCount = $fleet->drivers()->count();
        $ridesCount = $fleet->rides()->count();
        $activeRides = $fleet->rides()->whereIn('status', [1, 2])->count(); // active/running rides

        return view('fleet.dashboard', compact('pageTitle', 'fleet', 'driversCount', 'ridesCount', 'activeRides'));
    }

    public function drivers()
    {
        $pageTitle = 'Conductores - Flota';
        $fleet = $this->getFleet();
        $drivers = $fleet->drivers()->paginate(10);
        return view('fleet.drivers', compact('pageTitle', 'fleet', 'drivers'));
    }

    public function addDriver(Request $request)
    {
        $request->validate([
            'username_or_email' => 'required|string',
        ]);

        $driver = Driver::where('username', $request->username_or_email)
            ->orWhere('email', $request->username_or_email)
            ->first();

        if (!$driver) {
            return back()->withNotify([['error', 'Conductor no encontrado']]);
        }

        $fleet = $this->getFleet();
        $driver->update(['fleet_id' => $fleet->id]);

        return back()->withNotify([['success', 'Conductor asignado exitosamente a tu flota']]);
    }

    public function fares()
    {
        $pageTitle = 'Configurar Tarifas - Flota';
        $fleet = $this->getFleet();
        $zones = \App\Models\Zone::active()->orderBy('name')->get();
        return view('fleet.fares', compact('pageTitle', 'fleet', 'zones'));
    }

    public function updateFares(Request $request)
    {
        $request->validate([
            'base_fare'       => 'nullable|numeric|min:0',
            'rate_per_km'     => 'nullable|numeric|min:0',
            'rate_per_minute' => 'nullable|numeric|min:0',
            'zone_id'         => 'nullable|exists:zones,id',
        ]);

        $fleet = $this->getFleet();
        $fleet->update($request->only('base_fare', 'rate_per_km', 'rate_per_minute', 'zone_id'));

        return back()->withNotify([['success', 'Configuración actualizada exitosamente']]);
    }
}
