<footer class="footer footer-transparent d-print-none">
    <div class="container-xl">
        <div class="row text-center align-items-center flex-row-reverse">
            <div class="hr mb-3 mt-0"></div>
            <div class="col-12 text-center d-flex flex-wrap justify-content-center align-items-center">
                {{ __('Copyright © :year, Development by', ['year' => date('Y')]) }}
                <a href="{{ config('merdeka.company_url') }}" target="_blank" rel="noopener" class="fw-normal ms-1">
                    {{ config('merdeka.company_name') }}</a>
                <span class="badge mdka-bg-gray-700 text-white rounded-2 ms-2">
                    v{{ config('merdeka.app_version') }}
                </span>
            </div>
        </div>
    </div>
</footer>
