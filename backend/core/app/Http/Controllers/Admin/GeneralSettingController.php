<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Frontend;
use App\Rules\FileTypeValidate;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GeneralSettingController extends Controller
{

    public function general()
    {
        $pageTitle = __('General Setting');
        $timezones       = timezone_identifiers_list();
        $currentTimezone = array_search(config('app.timezone'), $timezones);
        $countries       = json_decode(file_get_contents(resource_path('views/partials/country.json')));

        return view('admin.setting.general', compact('pageTitle', 'timezones', 'currentTimezone', 'countries'));
    }

    public function rideSetting()
    {
        $pageTitle = __('Ride Setting');
        return view('admin.setting.ride', compact('pageTitle'));
    }
    public function googleMaps()
    {
        $pageTitle = __('Google Maps Setting');
        return view('admin.setting.google_map', compact('pageTitle'));
    }

    public function generalUpdate(Request $request)
    {
        $countries = collect(json_decode(file_get_contents(resource_path('views/partials/country.json'))));
        $request->validate([
            'site_name'               => 'required|string|max:40',
            'cur_text'                => 'required|string|max:40',
            'cur_sym'                 => 'required|string|max:40',
            'base_color'              => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'secondary_color'         => 'nullable|regex:/^[a-f0-9]{6}$/i',
            'timezone'                => 'required|integer',
            'currency_format'         => 'required|in:1,2,3',
            'paginate_number'         => 'required|integer',
            'time_format'             => ['required', Rule::in(supportedTimeFormats())],
            'date_format'             => ['required', Rule::in(supportedDateFormats())],
            'thousand_separator'      => ['required', Rule::in(array_keys(supportedThousandSeparator()))],
            'allow_precision'         => 'required|integer|gt:0|lte:8',
            'preloader_image'         => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png', 'gif'])],
            'notification_audio'      => ['nullable', new FileTypeValidate(['mp3', 'audio'])],
            'operating_country'       => ['required', 'array', 'min:1', 'max:5'],
            'operating_country.*'     => ['required', Rule::in($countries->keys()->toArray())],
        ]);

        $timezones = timezone_identifiers_list();
        $timezone  = @$timezones[$request->timezone] ?? 'UTC';

        $general                          = gs();
        $general->site_name               = $request->site_name;
        $general->cur_text                = $request->cur_text;
        $general->cur_sym                 = $request->cur_sym;
        $general->paginate_number         = $request->paginate_number;
        $general->base_color              = str_replace('#', '', $request->base_color);
        $general->secondary_color         = str_replace('#', '', $request->secondary_color);
        $general->currency_format         = $request->currency_format;
        $general->time_format             = $request->time_format;
        $general->date_format             = $request->date_format;
        $general->allow_precision         = $request->allow_precision;
        $general->thousand_separator      = $request->thousand_separator;
        $general->timezone                = $timezone;
        $general->operating_country       = $countries->only($request->operating_country)->toArray();

        if ($request->hasFile('preloader_image')) {
            try {
                $general->preloader_image = fileUploader($request->preloader_image, getFilePath('preloader'), old: $general->preloader_image);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the preloader image'];
                return back()->withNotify($notify);
            }
        }
        if ($request->hasFile('notification_audio')) {
            try {
                $general->notification_audio = fileUploader($request->notification_audio, getFilePath('notification_audio'), old: $general->notification_audio);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload the notification audio'];
                return back()->withNotify($notify);
            }
        }

        $general->save();

        $timezoneFile = config_path('timezone.php');
        $content      = '<?php $timezone = "' . $timezone . '" ?>';
        file_put_contents($timezoneFile, $content);


        $notify[] = ['success', __('General setting updated successfully')];
        return back()->withNotify($notify);
    }
    public function googleMapsUpdate(Request $request)
    {
        $request->validate([
            'google_maps_api' => 'required',
        ]);

        $general                  = gs();
        $general->google_maps_api = $request->google_maps_api;
        $general->save();

        $notify[] = ['success', __('Google maps API key updated successfully')];
        return back()->withNotify($notify);
    }

    public function rideSettingUpdate(Request $request)
    {

        $request->validate([
            'distance_unit'               => 'required|in:1,2',
            'min_distance'                => 'required|numeric|gte:0',
            'min_fare'                    => 'required|numeric|min:1',
            'delivery_min_fee'            => 'required|numeric|min:0',
            'delivery_fee_per_km'         => 'required|numeric|min:0',
            'delivery_base_km'            => 'required|numeric|min:0.5',
            'delivery_time_rate'          => 'required|numeric|min:0',
            'delivery_avg_speed'          => 'required|numeric|min:5|max:80',
            'delivery_surge'              => 'required|numeric|min:1|max:5',
            'delivery_coverage_radius'    => 'required|numeric|min:0',
            'delivery_convenient_fee_limit' => 'required|numeric|min:0',
            'favor_min_fee'               => 'required|numeric|min:0',
            'favor_fee_per_km'            => 'required|numeric|min:0',
            'favor_base_km'               => 'required|numeric|min:0.5',
            'favor_time_rate'             => 'required|numeric|min:0',
            'favor_avg_speed'             => 'required|numeric|min:5|max:80',
            'favor_surge'                 => 'required|numeric|min:1|max:5',
            'favor_coverage_radius'       => 'required|numeric|min:0',
            'user_cancellation_limit'     => 'required',
            'user_cancellation_penalty'   => 'required',
            'driver_cancellation_limit'   => 'required',
            'driver_cancellation_penalty' => 'required',
            'ride_cancel_time'            => 'required',
            'negative_balance_driver'     => 'required|lte:0',
            'tips_suggest_amount'         => ['required', 'array', 'min:1', 'max:5'],
            'tips_suggest_amount.*'       => ['required', 'numeric'],
        ]);

        $general                              = gs();
        $general->min_distance                = $request->min_distance;
        $general->min_fare                    = $request->min_fare;
        $general->delivery_min_fee            = $request->delivery_min_fee;
        $general->delivery_fee_per_km         = $request->delivery_fee_per_km;
        $general->delivery_base_km           = $request->delivery_base_km;
        $general->delivery_time_rate         = $request->delivery_time_rate;
        $general->delivery_avg_speed         = $request->delivery_avg_speed;
        $general->delivery_surge             = $request->delivery_surge;
        $general->delivery_coverage_radius    = $request->delivery_coverage_radius;
        $general->delivery_convenient_fee_limit = $request->delivery_convenient_fee_limit;
        $general->favor_min_fee               = $request->favor_min_fee;
        $general->favor_fee_per_km            = $request->favor_fee_per_km;
        $general->favor_base_km               = $request->favor_base_km;
        $general->favor_time_rate             = $request->favor_time_rate;
        $general->favor_avg_speed             = $request->favor_avg_speed;
        $general->favor_surge                 = $request->favor_surge;
        $general->favor_coverage_radius       = $request->favor_coverage_radius;
        $general->user_cancellation_limit     = $request->user_cancellation_limit;
        $general->user_cancellation_penalty   = $request->user_cancellation_penalty;
        $general->driver_cancellation_limit   = $request->driver_cancellation_limit;
        $general->driver_cancellation_penalty = $request->driver_cancellation_penalty;
        $general->ride_cancel_time            = $request->ride_cancel_time;
        $general->distance_unit               = $request->distance_unit;
        $general->negative_balance_driver     = $request->negative_balance_driver;
        $general->tips_suggest_amount         = $request->tips_suggest_amount ?? 10;
        $general->save();


        $notify[] = ['success', __('Ride setting updated successfully')];
        return back()->withNotify($notify);
    }

    public function systemConfiguration()
    {
        $pageTitle = __('System Configuration');
        $configurations = json_decode(file_get_contents(resource_path('views/admin/setting/configuration.json')));
        return view('admin.setting.configuration', compact('pageTitle', 'configurations'));
    }


    public function pusherConfiguration()
    {
        $pageTitle = __('Pusher Configuration');
        $general   = gs();
        return view('admin.setting.pusher_configuration', compact('pageTitle', 'general'));
    }

    public function pusherConfigurationUpdate(Request $request)
    {
        $request->validate([
            'pusher_app_key'          => 'required',
            'pusher_app_id'           => 'required',
            'pusher_app_secret'       => 'required',
            'pusher_cluster'          => 'required',
        ]);

        //pusher config set to file
        $pusherConfigFile = config_path('pusher.php');

        $pusherConfig = '<?php' . PHP_EOL .
            '$pusherAppId       = ' . '"' . $request->pusher_app_id . '"' . ";" . PHP_EOL .
            '$pusherAppKey      =' . '"' . $request->pusher_app_key . '"' . ";" . PHP_EOL .
            '$pusherAppSecret   =' . '"' . $request->pusher_app_secret . '"' . ';' . PHP_EOL .
            '$pusherAppCluster  =' . '"' . $request->pusher_cluster . '"' . ';' . PHP_EOL .
            "?>";

        file_put_contents($pusherConfigFile,  $pusherConfig);

        $pusherConfig = [
            'app_key'    => $request->pusher_app_key,
            'app_id'     => $request->pusher_app_id,
            'app_secret' => $request->pusher_app_secret,
            'cluster'    => $request->pusher_cluster,
        ];

        $general                = gs();
        $general->pusher_config = $pusherConfig;
        $general->save();

        $notify[] = ['success', __('Pusher configuration updated successfully')];
        return back()->withNotify($notify);
    }


    public function systemConfigurationUpdate($key)
    {
        try {
            $general   = gs();
            $newStatus = !$general->$key;

            $general->$key = $newStatus;
            $general->save();

            return response()->json([
                'success'    => true,
                'new_status' => $newStatus
            ]);
        } catch (Exception $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage()
            ]);
        }
    }


    public function logoIcon()
    {
        $pageTitle = __('Brand Setting');
        return view('admin.setting.logo_icon', compact('pageTitle'));
    }

    public function logoIconUpdate(Request $request)
    {
        $request->validate([
            'logo'    => ['image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
            'favicon' => ['image', new FileTypeValidate(['png'])],
        ]);
        $path = getFilePath('logoIcon');

        if ($request->hasFile('logo')) {
            try {
                fileUploader($request->logo, $path, filename: 'logo.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'No se pudo subir el logo: ' . $exp->getMessage()];
                return back()->withNotify($notify);
            }
        }
        if ($request->hasFile('logo_dark')) {
            try {
                fileUploader($request->logo_dark, $path, filename: 'logo_dark.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'No se pudo subir el logo dark: ' . $exp->getMessage()];
                return back()->withNotify($notify);
            }
        }

        if ($request->hasFile('favicon')) {
            try {
                fileUploader($request->favicon, $path, filename: 'favicon.png');
            } catch (\Exception $exp) {
                $notify[] = ['error', 'No se pudo subir el favicon: ' . $exp->getMessage()];
                return back()->withNotify($notify);
            }
        }
        $notify[] = ['success', __('Brand setting updated successfully')];
        return back()->withNotify($notify);
    }

    public function customCss()
    {
        $pageTitle = __('Custom CSS');
        $file        = activeTemplate(true) . 'css/custom.css';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.custom_css', compact('pageTitle', 'fileContent'));
    }

    public function sitemap()
    {
        $pageTitle = __('Sitemap XML');
        $file        = 'sitemap.xml';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.sitemap', compact('pageTitle', 'fileContent'));
    }

    public function sitemapSubmit(Request $request)
    {
        $file = 'sitemap.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->sitemap);
        $notify[] = ['success', __('Sitemap updated successfully')];
        return back()->withNotify($notify);
    }



    public function robot()
    {
        $pageTitle = __('Robots TXT');
        $file        = 'robots.xml';
        $fileContent = @file_get_contents($file);
        return view('admin.setting.robots', compact('pageTitle', 'fileContent'));
    }

    public function robotSubmit(Request $request)
    {
        $file = 'robots.xml';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->robots);
        $notify[] = ['success', __('Robots txt updated successfully')];
        return back()->withNotify($notify);
    }


    public function customCssSubmit(Request $request)
    {
        $file = activeTemplate(true) . 'css/custom.css';
        if (!file_exists($file)) {
            fopen($file, "w");
        }
        file_put_contents($file, $request->css);
        $notify[] = ['success', __('CSS updated successfully')];
        return back()->withNotify($notify);
    }

    public function maintenanceMode()
    {
        $pageTitle = __('Maintenance Mode');
        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        return view('admin.setting.maintenance', compact('pageTitle', 'maintenance'));
    }

    public function maintenanceModeSubmit(Request $request)
    {
        $request->validate([
            'description' => 'required',
            'image'       => ['nullable', new FileTypeValidate(['jpg', 'jpeg', 'png'])]
        ]);
        $general                   = gs();
        $general->maintenance_mode = $request->status ? Status::ENABLE : Status::DISABLE;
        $general->save();

        $maintenance = Frontend::where('data_keys', 'maintenance.data')->firstOrFail();
        $image       = @$maintenance->data_values->image;

        if ($request->hasFile('image')) {
            try {
                $old   = $image;
                $image = fileUploader($request->image, getFilePath('maintenance'), getFileSize('maintenance'), $old);
            } catch (\Exception $exp) {
                $notify[] = ['error', 'Couldn\'t upload your image'];
                return back()->withNotify($notify);
            }
        }

        $maintenance->data_values = [
            'description' => $request->description,
            'image'       => $image
        ];
        $maintenance->save();

        $notify[] = ['success', __('Maintenance mode updated successfully')];
        return back()->withNotify($notify);
    }

    public function cookie()
    {
        $pageTitle = __('GDPR Cookie');
        $cookie    = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        return view('admin.setting.cookie', compact('pageTitle', 'cookie'));
    }

    public function cookieSubmit(Request $request)
    {
        $request->validate([
            'short_desc'  => 'required|string|max:255',
            'description' => 'required',
        ]);
        $cookie              = Frontend::where('data_keys', 'cookie.data')->firstOrFail();
        $cookie->data_values = [
            'short_desc'  => $request->short_desc,
            'description' => $request->description,
            'status'      => $request->status ? Status::ENABLE : Status::DISABLE,
        ];
        $cookie->save();
        $notify[] = ['success', __('Cookie policy updated successfully')];
        return back()->withNotify($notify);
    }
}
