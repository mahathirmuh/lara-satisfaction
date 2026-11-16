<div class="card" id="userFilterCard" style="display: none;">
    <div class="card-header p-2">
        <h4 class="mb-0">{{ __('Filter') }}</h4>
    </div>
    <div class="card-body p-2">
        <div class="row g-2">
            <div class="col-md-3">
                <label for="companySelectFilter" class="form-label">{{ __('Company') }}</label>
                <select id="companySelectFilter" class="form-select" multiple="multiple">
                    @foreach ($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="departmentSelectFilter" class="form-label">{{ __('Department') }}</label>
                <select id="departmentSelectFilter" class="form-select" multiple="multiple">
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}">{{ $department->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="roleSelectFilter" class="form-label">{{ __('Role') }}</label>
                <select id="roleSelectFilter" class="form-select" multiple="multiple">
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="activeSelectFilter" class="form-label">{{ __('Status') }}</label>
                <select id="activeSelectFilter" class="form-select">
                    <option value="">{{ __('All status') }}</option>
                    <option value="1">{{ __('Active') }}</option>
                    <option value="0">{{ __('Inactive') }}</option>
                </select>
            </div>
            <div class="col-12 mt-2 d-flex gap-2">
                <x-global.btn-detail label="" color="red" size="sm" outline="true" data-bs-toggle="tooltip"
                    onclick="resetUserFilters(); userTable.applyFilters();" data-bs-title="{{ __('Clear Filter') }}">
                    <x-slot:icon><i class="ti ti-x"></i></x-slot:icon>
                </x-global.btn-detail>
                <x-global.btn-detail label="" color="green" size="sm" outline="true" data-bs-toggle="tooltip"
                    onclick="userTable.applyFilters()" data-bs-title="{{ __('Apply Filter') }}">
                    <x-slot:icon><i class="ti ti-check"></i></x-slot:icon>
                </x-global.btn-detail>
            </div>
        </div>
    </div>
</div>

@push('javascript')
    <script>
        $(function() {
            $('#userFilterCard #companySelectFilter').select2({
                theme: 'bootstrap-5',
                placeholder: @json(__('Select Company')),
                allowClear: true,
                dropdownParent: $('#userFilterCard'),
                width: '100%',
                closeOnSelect: false
            });
            $('#userFilterCard #departmentSelectFilter').select2({
                theme: 'bootstrap-5',
                placeholder: @json(__('Select Department')),
                allowClear: true,
                dropdownParent: $('#userFilterCard'),
                width: '100%',
                closeOnSelect: false
            });
            $('#userFilterCard #roleSelectFilter').select2({
                theme: 'bootstrap-5',
                placeholder: @json(__('Select Role')),
                allowClear: true,
                dropdownParent: $('#userFilterCard'),
                width: '100%',
                closeOnSelect: false
            });
        });

        function userFilters() {
            return {
                company: $('#companySelectFilter').val() || [],
                department: $('#departmentSelectFilter').val() || [],
                role: $('#roleSelectFilter').val() || [],
                is_active: $('#activeSelectFilter').val(),
            };
        }

        function resetUserFilters() {
            $('#companySelectFilter').val(null).trigger('change');
            $('#departmentSelectFilter').val(null).trigger('change');
            $('#roleSelectFilter').val(null).trigger('change');
            $('#activeSelectFilter').val('');
        }
    </script>
@endpush
