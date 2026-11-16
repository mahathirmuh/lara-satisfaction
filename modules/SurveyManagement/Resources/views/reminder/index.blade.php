@extends('main.index')

@push('style')
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2.min.css') }}?v=4.1.0-rc.0" />
    <link rel="stylesheet" href="{{ asset('assets/libs/select2/select2-bootstrap.min.css') }}" />
    <style>
        /* The people search: the size of the other fields, a person on two lines (name, then e-mail and employee ID). */
        /* (As specific as the theme's own rules, which set 1rem.) */
        .select2-container--bootstrap-5 .people-selection.select2-selection--multiple .select2-search .select2-search__field,
        .select2-container--bootstrap-5 .people-dropdown.select2-dropdown .select2-search .select2-search__field {
            font-size: .8125rem;
        }

        .select2-container--bootstrap-5 .people-selection.select2-selection--multiple .select2-selection__rendered .select2-selection__choice {
            font-size: .75rem;
        }

        .select2-container--bootstrap-5 .people-dropdown.select2-dropdown .select2-results__options {
            max-height: 264px;
        }

        .select2-container--bootstrap-5 .people-dropdown.select2-dropdown .select2-results__options .select2-results__option {
            padding: .4rem .65rem;
            font-size: .8125rem;
            line-height: 1.35;
        }

        .people-option-name {
            font-weight: 500;
        }

        .people-option-meta {
            font-size: .72rem;
            color: var(--tblr-secondary);
            overflow-wrap: anywhere;
        }

        .select2-results__option--highlighted .people-option-meta {
            color: inherit;
            opacity: .8;
        }
    </style>
@endpush

@section('page-title', __('Reminders'))

@section('content')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-send me-1"></i> {{ __('Send a Reminder') }}</h4>
                </div>
                <div class="card-body">
                    @if ($surveys->isEmpty())
                        <x-global.empty-state icon="clipboard-off" :title="__('No survey is open right now')"
                            :subtitle="__('Reminders can only be sent for a survey that is open.')" />
                    @else
                        <form id="reminderForm" novalidate>
                            <div class="mb-3">
                                <label for="reminderSurvey" class="form-label required">{{ __('Survey') }}</label>
                                <select id="reminderSurvey" class="form-select">
                                    @foreach ($surveys as $survey)
                                        <option value="{{ $survey->id }}" data-pending="{{ $pendingCounts[$survey->id] }}">
                                            {{ $survey->title }} ({{ $survey->periodLabel() }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label required">{{ __('Recipients') }}</label>
                                <label class="form-check">
                                    <input class="form-check-input" type="radio" name="audience" value="all" checked>
                                    <span class="form-check-label">
                                        {{ __('Everyone who has not completed it') }}
                                        <span class="badge bg-blue-lt ms-1" id="pendingCount"></span>
                                    </span>
                                </label>
                                <label class="form-check mb-0">
                                    <input class="form-check-input" type="radio" name="audience" value="custom">
                                    <span class="form-check-label">{{ __('Chosen people') }}</span>
                                </label>
                            </div>

                            <div class="mb-3" id="reminderUsersGroup" style="display: none">
                                <label for="reminderUsers" class="form-label required">{{ __('People') }}</label>
                                <select id="reminderUsers" class="form-select" multiple="multiple"></select>
                                <small class="form-hint">{{ __('Search by name, email or employee ID. Choose yourself to try it out.') }}</small>
                            </div>

                            <div class="alert alert-info py-2 small mb-2" role="alert">
                                <i class="ti ti-info-circle me-1"></i>
                                {{ __('Each person gets a notification in the application and an e-mail, in their own language, with a link to the survey. People who completed the survey already are left out.') }}
                            </div>
                            <div class="text-secondary small mb-3">
                                {{ __('Reminders go to the employees of the assessed departments, imported every day from Active Directory.') }}
                            </div>

                            @if ($can['create'])
                                <div class="d-flex justify-content-end">
                                    <x-global.btn-detail :label="__('Send Reminder')" color="blue" size="sm" type="submit">
                                        <x-slot:icon><i class="ti ti-send"></i></x-slot:icon>
                                    </x-global.btn-detail>
                                </div>
                            @endif
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card">
                <div class="card-header p-2 px-3">
                    <h4 class="mb-0"><i class="ti ti-history me-1"></i> {{ __('Reminder History') }}</h4>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-vcenter mdka-border-gray-100 text-nowrap mb-0">
                        <thead>
                            <tr class="mdka-bg-gray-50">
                                <th class="fs-5 py-3">{{ __('Sent At') }}</th>
                                <th class="fs-5 py-3">{{ __('Survey') }}</th>
                                <th class="fs-5 py-3">{{ __('Recipients') }}</th>
                                <th class="fs-5 py-3 text-center">{{ __('Reminded') }}</th>
                                <th class="fs-5 py-3">{{ __('Sent By') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($history as $send)
                                <tr>
                                    <td>{{ $send['sent_at']->translatedFormat(__('d M Y H:i')) }}</td>
                                    <td>{{ $send['survey'] ?? '-' }}</td>
                                    <td>{{ $send['audience'] }}</td>
                                    <td class="text-center">
                                        {{ $send['recipients'] }}
                                        @if ($send['skipped'] > 0)
                                            <span class="text-secondary small">({{ __(':count left out', ['count' => $send['skipped']]) }})</span>
                                        @endif
                                    </td>
                                    <td>{{ $send['sent_by'] }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <x-global.empty-state icon="bell-off" :title="__('No reminder has been sent yet')" />
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('javascript')
    <script src="{{ asset('assets/libs/select2/select2.min.js') }}?v=4.1.0-rc.0"></script>
    <script>
        const urlReminderStore = @json(route('survey-management.reminder.store'));
        const urlReminderRecipients = @json(route('survey-management.reminder.recipients'));
        const reminderTexts = {
            chooseSomeone: @json(__('Choose at least one person.')),
            confirmAll: @json(__('This will remind :count people who have not completed the survey.')),
            confirmCustom: @json(__('This will remind the :count chosen people.')),
            placeholder: @json(__('Search people…')),
            searching: @json(__('Searching…')),
            noResults: @json(__('No people found')),
            maximumSelected: @json(__('You can choose up to :count people.')),
            completed: @json(__('completed')),
        };

        $(function() {
            const form = $('#reminderForm');

            if (!form.length) {
                return;
            }

            const surveySelect = $('#reminderSurvey');
            // A person in the list: the name, and under it the e-mail and employee ID (as text, never as markup).
            function personOption(person) {
                if (!person.id || !person.name) {
                    return person.text;
                }

                const meta = [person.email, person.employee_id].filter(Boolean).join(' · ');
                const option = $('<div>').append($('<div class="people-option-name">').text(person.name));

                option.append($('<div class="people-option-meta">').text(meta + (person.disabled ? ' · ' + reminderTexts.completed : '')));

                return option;
            }

            const people = $('#reminderUsers').select2({
                theme: 'bootstrap-5',
                width: '100%',
                selectionCssClass: 'people-selection',
                dropdownCssClass: 'people-dropdown',
                templateResult: personOption,
                templateSelection: (person) => person.name || person.text,
                placeholder: reminderTexts.placeholder,
                closeOnSelect: false,
                maximumSelectionLength: @json($maxChosen),
                ajax: {
                    url: urlReminderRecipients,
                    dataType: 'json',
                    delay: 250,
                    data: (params) => ({
                        search: params.term || '',
                        survey_id: surveySelect.val(),
                    }),
                    processResults: (response) => ({
                        results: response.data
                    }),
                },
                language: {
                    searching: () => reminderTexts.searching,
                    noResults: () => reminderTexts.noResults,
                    maximumSelected: (args) => reminderTexts.maximumSelected.replace(':count', args.maximum),
                },
            });

            function pendingCount() {
                return parseInt(surveySelect.find('option:selected').data('pending'), 10) || 0;
            }

            function audience() {
                return $('input[name="audience"]:checked').val();
            }

            function refreshForm() {
                $('#pendingCount').text(pendingCount());
                $('#reminderUsersGroup').toggle(audience() === 'custom');
            }

            surveySelect.on('change', function() {
                people.val(null).trigger('change');
                refreshForm();
            });
            $('input[name="audience"]').on('change', refreshForm);
            refreshForm();

            form.on('submit', function(event) {
                event.preventDefault();

                const chosen = people.val() || [];
                const isCustom = audience() === 'custom';

                if (isCustom && !chosen.length) {
                    showError([reminderTexts.chooseSomeone]);

                    return;
                }

                saveRecord({
                    url: urlReminderStore,
                    method: 'POST',
                    data: {
                        survey_id: surveySelect.val(),
                        audience: audience(),
                        user_ids: isCustom ? chosen : [],
                    },
                    confirmText: (isCustom ? reminderTexts.confirmCustom : reminderTexts.confirmAll)
                        .replace(':count', isCustom ? chosen.length : pendingCount()),
                    onSuccess: () => setTimeout(() => window.location.reload(), 1500),
                });
            });
        });
    </script>
@endpush
