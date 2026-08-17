@extends('admin.layouts.master')
@section('content')
    <main class="dashboard">
        @include('fleet.partials.sidenav')
        <section class="dashboard__area">
            <div class="container-fluid">
                @include('admin.partials.topnav')
                <div class="dashboard__area-header flex-wrap gap-2">
                    <h3 class="page-title">{{ __($pageTitle) }}</h3>
                </div>
                <div class="dashboard__area-inner p-0 mt-4">
                    @yield('panel')
                </div>
            </div>
        </section>
    </main>
@endsection
