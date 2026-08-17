@extends('admin.layouts.app')
@section('panel')
    <form action="{{ route('admin.frontend.sections.content', 'seo') }}" method="POST" enctype="multipart/form-data">
        <div class="row justify-content-center">
            @csrf
            <div class="col-xxl-3 col-xl-12">
                <div class="form-group">
                    <div class="bg--white rounded p-3">
                        <label class="form-label">@lang('SEO Image')</label>
                        <small class="d-block text-muted mb-2">1200x630px recomendado (Facebook/LinkedIn)</small>
                        <x-image-uploader :imagePath="getImage(getFilePath('seo') . '/' . @$seo->data_values->image, getFileSize('seo'))" :size="getFileSize('seo')" :required="false" name="image_input" />
                    </div>
                </div>
            </div>
            <div class="col-xxl-9 col-xl-12">
                <x-admin.ui.card>
                    <x-admin.ui.card.header>
                        <h4 class="card-title">@lang('SEO Configuration')</h4>
                        <small>@lang('Optimize for Google, Bing, and social media sharing')</small>
                    </x-admin.ui.card.header>
                    <x-admin.ui.card.body>
                        <input type="hidden" name="type" value="data">
                        <input type="hidden" name="seo_image" value="1">

                        <h5 class="mb-3"><i class="fas fa-globe"></i> @lang('Search Engine (Google / Bing)')</h5>

                        <div class="form-group">
                            <label>@lang('Meta Title') <small class="text-muted">(50-60 chars)</small></label>
                            <input type="text" class="form-control" name="social_title" maxlength="70"
                                value="{{ @$seo->data_values->social_title }}" required>
                            <small class="text-muted">@lang('Displayed in search results and browser tab')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('Meta Description') <small class="text-muted">(150-160 chars)</small></label>
                            <textarea name="description" rows="3" class="form-control" maxlength="200" required>{{ @$seo->data_values->description }}</textarea>
                            <small class="text-muted">@lang('Shown below the title in Google/Bing results')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('Meta Keywords')</label>
                            <span class="d-block text-muted fs-13 mb-2">@lang('Separate by comma or enter key')</span>
                            <select name="keywords[]" class="form-control select2-auto-tokenize select2-js-input"
                                multiple="multiple" required>
                                @if (@$seo->data_values->keywords)
                                    @foreach ($seo->data_values->keywords as $option)
                                        <option value="{{ $option }}" selected>{{ __($option) }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <div class="form-group">
                            <label>@lang('Robots Directive')</label>
                            <select name="robots" class="form-control">
                                <option value="index, follow" {{ (@$seo->data_values->robots ?? 'index, follow') == 'index, follow' ? 'selected' : '' }}>index, follow (default)</option>
                                <option value="noindex, follow" {{ (@$seo->data_values->robots ?? '') == 'noindex, follow' ? 'selected' : '' }}>noindex, follow</option>
                                <option value="index, nofollow" {{ (@$seo->data_values->robots ?? '') == 'index, nofollow' ? 'selected' : '' }}>index, nofollow</option>
                                <option value="noindex, nofollow" {{ (@$seo->data_values->robots ?? '') == 'noindex, nofollow' ? 'selected' : '' }}>noindex, nofollow</option>
                            </select>
                            <small class="text-muted">@lang('Control how search engines index this page')</small>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="fab fa-facebook"></i> @lang('Facebook / LinkedIn (Open Graph)')</h5>

                        <div class="form-group">
                            <label>@lang('OG Title') <small class="text-muted">(max 95 chars)</small></label>
                            <input type="text" class="form-control" name="og_title" maxlength="95"
                                value="{{ @$seo->data_values->og_title ?? @$seo->data_values->social_title }}">
                            <small class="text-muted">@lang('Title shown when shared on Facebook/LinkedIn')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('OG Description') <small class="text-muted">(max 300 chars)</small></label>
                            <textarea name="og_description" rows="2" class="form-control" maxlength="300">{{ @$seo->data_values->og_description ?? @$seo->data_values->social_description }}</textarea>
                            <small class="text-muted">@lang('Description shown when shared on Facebook/LinkedIn')</small>
                        </div>

                        <div class="form-group">
                            <label>@lang('OG Type')</label>
                            <select name="og_type" class="form-control">
                                <option value="website" {{ (@$seo->data_values->og_type ?? 'website') == 'website' ? 'selected' : '' }}>website</option>
                                <option value="business.business" {{ (@$seo->data_values->og_type ?? '') == 'business.business' ? 'selected' : '' }}>business.business</option>
                                <option value="article" {{ (@$seo->data_values->og_type ?? '') == 'article' ? 'selected' : '' }}>article</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <h5 class="mb-3"><i class="fab fa-twitter"></i> @lang('Twitter / X')</h5>

                        <div class="form-group">
                            <label>@lang('Twitter Title') <small class="text-muted">(max 70 chars)</small></label>
                            <input type="text" class="form-control" name="twitter_title" maxlength="70"
                                value="{{ @$seo->data_values->twitter_title ?? @$seo->data_values->social_title }}">
                        </div>

                        <div class="form-group">
                            <label>@lang('Twitter Description') <small class="text-muted">(max 200 chars)</small></label>
                            <textarea name="twitter_description" rows="2" class="form-control" maxlength="200">{{ @$seo->data_values->twitter_description ?? @$seo->data_values->social_description }}</textarea>
                        </div>

                        <div class="form-group">
                            <label>@lang('Twitter Handle')</label>
                            <input type="text" class="form-control" name="twitter_site" placeholder="@liztodelivery"
                                value="{{ @$seo->data_values->twitter_site ?? '@liztodelivery' }}">
                        </div>

                        <x-permission_check permission="update seo">
                            <x-admin.ui.btn.submit />
                        </x-permission_check>
                    </x-admin.ui.card.body>
                </x-admin.ui.card>
            </div>
        </div>
    </form>
@endsection

@push('style')
    <style>
        .image-upload__placeholder {
            border: unset;
            box-shadow: unset;
        }

        .image-upload__icon {
            bottom: 5px;
        }
    </style>
@endpush
