@props(['widget'])
<div class="row responsive-row">
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.driver.all')" variant="primary" title="{{ __('Total Driver') }}" :value="$widget['total_driver']"
            icon="las la-users" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.driver.active')" variant="success" title="{{ __('Active Driver') }}" :value="$widget['active_driver']"
            icon="las la-user-check" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.driver.unverified')" variant="warning" title="{{ __('Document Unverified Driver') }}" :value="$widget['document_unverified_driver']"
            icon="la la-list" :currency="false" />
    </div>
    <div class="col-xxl-3 col-sm-6">
        <x-admin.ui.widget.four :url="route('admin.driver.vehicle.unverified')" variant="danger" title="{{ __('Vehicle Unverified Driver') }}" :value="$widget['vehicle_unverified_driver']"
            icon="las la-car" :currency="false" />
    </div>
</div>
