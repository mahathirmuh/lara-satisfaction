<!-- Image Preview (opened by links with data-image-preview, see showImagePreview) -->
<div id="imagePreviewModal" class="modal modal-blur fade" tabindex="-1" aria-labelledby="imagePreviewTitle"
    aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="imagePreviewTitle">{{ __('Image') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <div class="modal-body p-2 image-preview-body">
                <img id="imagePreviewImage" src="" alt="" class="image-preview-image">
            </div>
            <div class="modal-footer justify-content-between gap-2">
                <div id="imagePreviewCaption" class="text-secondary small"></div>
                <span class="text-secondary small text-nowrap"><i class="ti ti-zoom-in me-1"></i>{{ __('Click the image to zoom') }}</span>
            </div>
        </div>
    </div>
</div>

<style>
    .image-preview-body {
        max-height: 76vh;
        overflow: auto;
        background: var(--mdka-gray-100);
    }

    .image-preview-image {
        display: block;
        margin: 0 auto;
        max-width: 100%;
        max-height: calc(76vh - 1rem);
        object-fit: contain;
        cursor: zoom-in;
    }

    .image-preview-body.is-zoomed .image-preview-image {
        max-width: none;
        max-height: none;
        cursor: zoom-out;
    }
</style>

<!-- Download Loader -->
<div id="download-loader" class="modal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-body text-center p-5">
                <h3 class="mb-0">{{ __('Processing Your Document') }}</h3>
                <img class="w-100 my-4" src="{{ asset('assets/img/property/download-animation.gif') }}"
                    alt="{{ __('Downloading') }}">
                <p class="text-muted mb-0">{{ __("Almost there! We're preparing your data. Kindly remain on this page.") }}</p>
            </div>
        </div>
    </div>
</div>
