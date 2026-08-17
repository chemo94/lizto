<?php

namespace App\Http\Controllers\Admin;

use App\Models\Banner;
use Illuminate\Http\Request;
use App\Rules\FileTypeValidate;
use App\Http\Controllers\Controller;

class BannerController extends Controller
{
    public function index()
    {
        $pageTitle = __('All Banners');
        $banners   = Banner::orderBy('sort_order')->orderBy('id', getOrderBy())->paginate(getPaginate());
        return view('admin.banner.index', compact('pageTitle', 'banners'));
    }

    public function store(Request $request, $id = 0)
    {
        $request->validate([
            'title'     => 'nullable|max:255',
            'link'      => 'nullable|url|max:500',
            'sort_order'=> 'nullable|integer|min:0',
            'type'      => 'required|in:taxi,delivery',
            'image'     => ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if ($id) {
            $banner      = Banner::findOrFail($id);
            $notification = __('Banner updated successfully');
            $imageRule   = ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])];
        } else {
            $banner      = new Banner();
            $notification = __('Banner added successfully');
            $imageRule   = ['required', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])];
        }

        $request->validate(['image' => $imageRule]);

        if ($request->hasFile('image')) {
            try {
                $banner->image = fileUploader($request->image, getFilePath('banner'), getFileSize('banner'), @$banner->image);
            } catch (\Exception $exp) {
                $notify[] = ['error', $exp->getMessage()];
                return back()->withNotify($notify);
            }
        }

        $banner->title      = $request->title;
        $banner->link       = $request->link;
        $banner->sort_order = $request->sort_order ?? 0;
        $banner->type       = $request->type;
        $banner->save();

        $notify[] = ['success', $notification];
        return back()->withNotify($notify);
    }

    public function status($id)
    {
        return Banner::changeStatus($id);
    }
}
