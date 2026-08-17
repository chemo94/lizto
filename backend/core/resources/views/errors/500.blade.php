@extends('errors.app')
@section('content')
    <div class="error-content__footer">
        <p class="error-content__message">
            <span class="title">@lang('Ups')! @lang('Error interno del servidor')</span>
            <span class="text">
                @lang("Estamos trabajando para resolver el problema. Inténtalo nuevamente en breve.")
            </span>
        </p>
        <a href="{{ route('home') }}" class="btn btn-outline--primary error-btn">
            <span class="btn--icon"><i class="fa-solid fa-house"></i></span>
            <span class="text">@lang('Volver al inicio')</span>
        </a>
    </div>
@endsection
