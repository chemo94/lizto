<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use App\Models\UserAddress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AddressController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $addresses = UserAddress::where('user_id', $user->id)->orderBy('is_default', 'desc')->get();

        return apiResponse('addresses', 'success', ['Tus direcciones'], [
            'addresses' => $addresses,
        ]);
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validator = Validator::make($request->all(), [
            'label'     => 'required|string|max:50',
            'address'   => 'required|string|max:500',
            'latitude'  => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_default'=> 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return apiResponse('validation_error', 'error', $validator->errors()->all());
        }

        if ($request->is_default) {
            UserAddress::where('user_id', $user->id)->update(['is_default' => false]);
        }

        $address = UserAddress::create([
            'user_id'    => $user->id,
            'label'      => $request->label,
            'address'    => $request->address,
            'latitude'   => $request->latitude,
            'longitude'  => $request->longitude,
            'is_default' => $request->is_default ?? false,
        ]);

        return apiResponse('address_saved', 'success', ['Dirección guardada'], ['address' => $address]);
    }

    public function delete($id)
    {
        $user = auth()->user();
        UserAddress::where('user_id', $user->id)->where('id', $id)->delete();

        return apiResponse('address_deleted', 'success', ['Dirección eliminada']);
    }
}
