@extends('main.index')

@section('page-title', __('Landing Page'))

@push('style')
    <style>
        .hero-preview {
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: cover;
            border-radius: .5rem;
            background: var(--mdka-gray-100);
        }
    </style>
@endpush

@section('content')
    <form method="POST" action="{{ route('master-data.landing-content.update') }}" enctype="multipart/form-data"
        id="landingContentForm" novalidate>
        @csrf
        @method('PUT')
        <div class="row g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-body p-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div>
                            <h3 class="mb-1">{{ __('Public Landing Page') }}</h3>
                            <div class="text-secondary">{{ __('Texts and hero image shown to employees before they sign in.') }}</div>
                        </div>
                        <div class="d-flex gap-2">
                            <x-global.btn-detail :label="__('View Page')" color="blue" size="sm" outline="true"
                                :href="route('landing')" target="_blank" rel="noopener">
                                <x-slot:icon><i class="ti ti-external-link"></i></x-slot:icon>
                            </x-global.btn-detail>
                            @if ($can['update'])
                                <x-global.btn-detail :label="__('Save')" color="blue" size="sm" type="submit">
                                    <x-slot:icon><i class="ti ti-device-floppy"></i></x-slot:icon>
                                </x-global.btn-detail>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Hero --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0"><i class="ti ti-layout-navbar me-1"></i> {{ __('Hero') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="hero_title" class="form-label required">{{ __('Headline') }}</label>
                            <input type="text" id="hero_title" name="hero_title" maxlength="120"
                                class="form-control @error('hero_title') is-invalid @enderror"
                                value="{{ old('hero_title', $content->hero_title) }}" required>
                            @error('hero_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-0">
                            <label for="hero_intro" class="form-label required">{{ __('Introduction') }}</label>
                            <textarea id="hero_intro" name="hero_intro" rows="4" maxlength="600"
                                class="form-control @error('hero_intro') is-invalid @enderror" required>{{ old('hero_intro', $content->hero_intro) }}</textarea>
                            @error('hero_intro')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="form-hint">{!! __('Write :placeholder where the title of the open survey should appear (shown in bold).', ['placeholder' => '<code>'.e($placeholder).'</code>']) !!}</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0"><i class="ti ti-photo me-1"></i> {{ __('Hero Image') }}</h4>
                    </div>
                    <div class="card-body">
                        <img src="{{ $content->heroImageUrl() }}" alt="{{ $content->hero_image_alt }}" id="heroImagePreview"
                            class="hero-preview border mb-3">
                        <input type="file" id="hero_image" name="hero_image" accept="image/jpeg,image/png,image/webp"
                            class="form-control @error('hero_image') is-invalid @enderror">
                        @error('hero_image')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-hint">{{ __('JPG, PNG or WebP, max 2 MB. Landscape images (16:10) look best.') }}</small>
                        @if ($content->hero_image_path)
                            <label class="form-check mt-2 mb-0">
                                <input class="form-check-input" type="checkbox" name="remove_hero_image" value="1">
                                <span class="form-check-label">{{ __('Remove the uploaded image (use the default Merdeka image)') }}</span>
                            </label>
                        @endif
                        <div class="mt-3">
                            <label for="hero_image_alt" class="form-label">{{ __('Image description') }}</label>
                            <input type="text" id="hero_image_alt" name="hero_image_alt" maxlength="255"
                                class="form-control @error('hero_image_alt') is-invalid @enderror"
                                value="{{ old('hero_image_alt', $content->hero_image_alt) }}"
                                placeholder="{{ __('Describe the image for screen readers') }}">
                            @error('hero_image_alt')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            {{-- Feedback & closing --}}
            <div class="col-lg-7">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0"><i class="ti ti-shield-lock me-1"></i> {{ __('Why Your Feedback Matters') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="feedback_title" class="form-label required">{{ __('Title') }}</label>
                            <input type="text" id="feedback_title" name="feedback_title" maxlength="120"
                                class="form-control @error('feedback_title') is-invalid @enderror"
                                value="{{ old('feedback_title', $content->feedback_title) }}" required>
                            @error('feedback_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="feedback_body" class="form-label required">{{ __('Text') }}</label>
                            <textarea id="feedback_body" name="feedback_body" rows="5" maxlength="2000"
                                class="form-control @error('feedback_body') is-invalid @enderror" required>{{ old('feedback_body', $content->feedback_body) }}</textarea>
                            @error('feedback_body')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="feedback_note" class="form-label">{{ __('Note') }}</label>
                            <input type="text" id="feedback_note" name="feedback_note" maxlength="120"
                                class="form-control @error('feedback_note') is-invalid @enderror"
                                value="{{ old('feedback_note', $content->feedback_note) }}">
                            @error('feedback_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header p-2 px-3">
                        <h4 class="mb-0"><i class="ti ti-flag me-1"></i> {{ __('Closing Call to Action') }}</h4>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="closing_title" class="form-label required">{{ __('Title') }}</label>
                            <input type="text" id="closing_title" name="closing_title" maxlength="160"
                                class="form-control @error('closing_title') is-invalid @enderror"
                                value="{{ old('closing_title', $content->closing_title) }}" required>
                            @error('closing_title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div>
                            <label for="closing_note" class="form-label">{{ __('Note') }}</label>
                            <input type="text" id="closing_note" name="closing_note" maxlength="255"
                                class="form-control @error('closing_note') is-invalid @enderror"
                                value="{{ old('closing_note', $content->closing_note) }}">
                            @error('closing_note')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="text-secondary small mt-3">
                            <i class="ti ti-info-circle me-1"></i>{{ __('Departments and their sections on the landing page come from Master Data › Departments and Sections.') }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('javascript')
    <script>
        $('#hero_image').on('change', function() {
            previewImage(this, '#heroImagePreview');
        });

        $('#landingContentForm').on('submit', function(event) {
            event.preventDefault();
            const form = this;

            confirmSave(@json(__('The public landing page will show the new content.'))).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    </script>
@endpush
