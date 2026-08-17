<?php

namespace App\Http\Controllers\Admin;

use App\Models\Zone;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;

class ZoneController extends Controller
{
    public function index()
    {
        $pageTitle = __('All Zones');
        $zones     = Zone::searchable(['name'])->orderBy('name')->orderBy('id', getOrderBy())->paginate(getPaginate());
        return view('admin.zone.index', compact('pageTitle', 'zones'));
    }

    public function create($id = 0)
    {
        $pageTitle = __('Add Zone');
        $coordinates = [];
        return view('admin.zone.create', compact('pageTitle', 'coordinates'));
    }
    public function edit($id = 0)
    {
        $pageTitle = __('Edit Zone');
        $zone        = Zone::findOrFail($id);
        $coordinates = $zone->coordinates;

        return view('admin.zone.create', compact('zone', 'pageTitle', 'coordinates'));
    }

    public function save(Request $request, $id = 0)
    {
        $isRequired = $id ? 'nullable' : 'required';
        $request->validate([
            'name'        => 'required|max:40|unique:zones,name,' . $id,
            'country'     => ['required', Rule::in(collect(gs('operating_country'))->keys()->toArray())],
            'coordinates' => [$isRequired],
        ]);


        if ($id) {
            $notification = __('Zone updated successfully');
            $zone         = Zone::findOrFail($id);
        } else {
            $notification = __('Zone added successfully');
            $zone          = new Zone();
        }

        $zone->name        = $request->name;
        $zone->country     = $request->country;

        if ($request->coordinates) {

            $decoded = json_decode($request->coordinates, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                // View sends JSON: [{lat: ..., lng: ...}, ...]
                $formattedCoordinates = [];
                foreach ($decoded as $point) {
                    $lat = isset($point['lat']) ? (float) $point['lat'] : null;
                    $lng = isset($point['lng']) ? (float) $point['lng']
                         : (isset($point['lang']) ? (float) $point['lang'] : null);

                    if ($lat !== null && $lng !== null) {
                        $formattedCoordinates[] = ['lat' => $lat, 'lng' => $lng];
                    }
                }
                $zone->coordinates = $formattedCoordinates;
            } else {
                // Fallback: legacy string format "(lat,lng),(lat,lng)"
                $coordinates          = explode('),', trim($request->coordinates, '()'));
                $formattedCoordinates = [];
                foreach ($coordinates as $coordinate) {
                    $parts = explode(',', $coordinate);
                    if (count($parts) >= 2) {
                        $formattedCoordinates[] = [
                            'lat' => (float) str_replace('(', '', $parts[0]),
                            'lng' => (float) $parts[1],
                        ];
                    }
                }
                $zone->coordinates = $formattedCoordinates;
            }
        }
        $zone->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function changeStatus($id)
    {
        return Zone::changeStatus($id);
    }
}
