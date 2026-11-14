@extends('main.index')

@section('page-title', $survey->title)

@push('style')
    <style>
        .rating-option .form-selectgroup-label {
            min-width: 96px;
        }

        .rating-scale-label {
            font-size: 11px;
            line-height: 1.2;
            text-align: center;
        }

        .survey-question+.survey-question {
            border-top: 1px dashed var(--tblr-border-color);
        }

        .survey-question.has-error {
            background: var(--mdka-red-50);
        }

        .survey-question.has-error .question-text,
        .section-choice.has-error .form-label {
            color: var(--tblr-red);
        }

        .section-choice.has-error .form-select {
            border-color: var(--tblr-red);
        }

        .department-badge {
            width: 44px;
            height: 44px;
            font-size: 1.4rem;
            flex: 0 0 44px;
        }

        .department-banner {
            display: block;
            width: 100%;
            height: 140px;
            object-fit: cover;
        }

        .section-heading {
            border-left: 4px solid var(--mdka-brand-500);
            padding-left: .75rem;
            color: var(--mdka-brand-600);
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .overview-image {
            display: block;
            border-radius: .5rem;
            overflow: hidden;
            background: var(--mdka-gray-100);
        }

        .overview-image img {
            display: block;
            width: 100%;
            aspect-ratio: 16 / 10;
            object-fit: contain;
        }

        .overview-title {
            color: var(--mdka-brand-600);
        }

        .role-overview {
            background: var(--mdka-brand-50);
            border: 1px solid var(--mdka-brand-100);
            text-align: justify;
        }

        .rating-description {
            background: var(--mdka-gray-50);
            border-left: 3px solid var(--mdka-gray-300);
        }

        .feedback-guidelines {
            background: var(--mdka-blue-50);
            border-left: 3px solid var(--mdka-blue-400);
            color: var(--mdka-blue-700);
        }

        .survey-nav {
            position: sticky;
            bottom: .75rem;
            z-index: 5;
        }
    </style>
@endpush

@section('content')
    <form id="surveyForm" method="POST" action="{{ route('survey-form.store', $survey) }}" novalidate>
        @csrf
        <div class="row g-3">
            {{-- Header --}}
            <div class="col-12">
                <div class="card rounded-3">
                    <div class="card-body">
                        <div class="d-flex flex-column flex-lg-row justify-content-between gap-3">
                            <div>
                                <h2 class="mb-1">{{ $survey->title }}</h2>
                                <div class="text-secondary small d-flex flex-wrap gap-3">
                                    <span><i class="ti ti-calendar-event me-1"></i>{{ $survey->periodLabel() }}</span>
                                    @if ($survey->isClosingSoon())
                                        <span class="fw-medium {{ $survey->daysLeft() <= 3 ? 'text-red' : 'text-orange' }}">
                                            <i class="ti ti-alarm me-1"></i>{{ $survey->deadlineLabel() }}
                                        </span>
                                    @endif
                                    <span><i class="ti ti-clock me-1"></i>&plusmn; {{ $survey->estimated_minutes }} minutes</span>
                                    <span><i class="ti ti-shield-lock me-1"></i>Anonymous &amp; confidential</span>
                                </div>
                                @if ($survey->description)
                                    <p class="text-secondary mt-2 mb-0">{{ $survey->description }}</p>
                                @endif
                            </div>
                            <div style="min-width: 240px">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span class="fw-medium">Progress</span>
                                    <span id="surveyProgressLabel" class="text-secondary"></span>
                                </div>
                                <div class="progress progress-sm">
                                    <div class="progress-bar bg-green" id="surveyProgress" style="width: 0%"
                                        role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-label="Survey progress">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Stepper --}}
            <div class="col-12">
                <div class="card rounded-3">
                    <div class="card-body py-3">
                        <x-global.steps id="surveySteps">
                            <x-global.steps.step :number="1" label="Personal Data" :active="true" class="cursor-pointer"
                                data-step-target="0" />
                            @foreach ($steps as $index => $step)
                                <x-global.steps.step :number="$index + 2" :label="$step['department']?->name ?? 'General'"
                                    class="cursor-pointer" data-step-target="{{ $index + 1 }}" />
                            @endforeach
                            <x-global.steps.step :number="$steps->count() + 2" label="Review & Submit"
                                class="cursor-pointer" data-step-target="{{ $steps->count() + 1 }}" />
                        </x-global.steps>
                    </div>
                </div>
            </div>

            {{-- Personal data (read from the employee account) --}}
            <div class="col-12 survey-step" data-step="0" data-title="Personal Data">
                <div class="card rounded-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-id-badge-2 me-2"></i>Personal Data</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">
                            Please check that the details below are yours before you start. They come from your employee
                            account; contact the administrator if something is wrong.
                        </p>
                        <div class="datagrid">
                            <div class="datagrid-item">
                                <div class="datagrid-title">Name</div>
                                <div class="datagrid-content">{{ $respondent->name }}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Employee ID</div>
                                <div class="datagrid-content">{{ $respondent->employee_id ?: '—' }}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Email</div>
                                <div class="datagrid-content">{{ $respondent->email }}</div>
                            </div>
                            <div class="datagrid-item">
                                <div class="datagrid-title">Company</div>
                                <div class="datagrid-content">{{ $respondent->company?->name ?? '—' }}</div>
                            </div>
                        </div>
                        <div class="alert alert-azure mt-4 mb-0" role="alert">
                            <div class="d-flex gap-2">
                                <i class="ti ti-shield-lock fs-2"></i>
                                <div>
                                    <strong>Your evaluation is anonymous.</strong> Your account is only used to make sure
                                    everyone answers once. Your answers are stored separately without your name; only your
                                    company is kept so results can be compared between companies. A draft you save is
                                    encrypted, visible only to you and deleted as soon as you submit.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- One step per department (general questions last) --}}
            @foreach ($steps as $index => $step)
                @php($department = $step['department'])
                @php($color = $department?->color ?? 'gray')
                @php($sections = $step['sections'])
                <div class="col-12 survey-step" data-step="{{ $index + 1 }}"
                    data-title="{{ $department?->name ?? 'General' }}" hidden>
                    <div class="card rounded-3 overflow-hidden">
                        @if ($department?->imageUrl())
                            <img src="{{ $department->imageUrl() }}" alt="{{ $department->name }}" class="department-banner">
                        @endif
                        <div class="card-header">
                            <div class="d-flex align-items-center gap-3">
                                <span
                                    class="department-badge d-inline-flex align-items-center justify-content-center rounded-3 mdka-bg-{{ $color }}-100 mdka-text-{{ $color }}-600">
                                    <i class="ti ti-{{ $department?->icon ?: 'message-circle' }}"></i>
                                </span>
                                <div>
                                    <h3 class="card-title mb-0">{{ $department?->name ?? 'General' }}</h3>
                                    <div class="text-secondary small">
                                        {{ $department ? $department->sectionsLabel($sections) : 'Overall experience across departments' }}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            @if ($department?->description)
                                <p class="text-secondary">{{ $department->description }}</p>
                            @endif

                            @if ($sections->isNotEmpty())
                                <div class="section-choice mb-4">
                                    <div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-2">
                                        <label class="form-label required mb-0" for="sectionChoice{{ $department->id }}">
                                            Select Section to Evaluate
                                        </label>
                                        <span class="badge bg-blue-lt section-progress"></span>
                                    </div>
                                    <select id="sectionChoice{{ $department->id }}" class="form-select section-select">
                                        @foreach ($sections as $section)
                                            <option value="{{ $section->id }}">{{ $section->name }}</option>
                                        @endforeach
                                    </select>
                                    <small class="form-hint">
                                        Evaluate every section of {{ $department->name }} before continuing; completed
                                        sections are marked with &#10003;.
                                    </small>
                                </div>

                                @foreach ($sections as $section)
                                    <div class="section-panel" data-section-id="{{ $section->id }}"
                                        data-section-name="{{ $section->name }}" @unless ($loop->first) hidden @endunless>
                                        <h3 class="section-heading">{{ $section->name }} Overview</h3>
                                        @if ($section->teamPhotoUrl() || $section->workSummaryUrl())
                                            <div class="row g-3 mb-3">
                                                @foreach ([
                                                    ['url' => $section->teamPhotoUrl(), 'title' => $section->name . ' Team Picture', 'caption' => $section->team_caption],
                                                    ['url' => $section->workSummaryUrl(), 'title' => $section->name . ' Work Summary', 'caption' => $section->work_summary_caption],
                                                ] as $picture)
                                                    @if ($picture['url'])
                                                        <div class="col-md-6">
                                                            <div class="card h-100">
                                                                <div class="card-body">
                                                                    <a href="{{ $picture['url'] }}" target="_blank" rel="noopener"
                                                                        class="overview-image mb-3" title="Click to enlarge" data-image-preview
                                                                        data-title="{{ $picture['title'] }}" data-caption="{{ $picture['caption'] }}">
                                                                        <img src="{{ $picture['url'] }}" alt="{{ $picture['title'] }}"
                                                                            loading="lazy">
                                                                    </a>
                                                                    <h4 class="overview-title mb-1">{{ $picture['title'] }}</h4>
                                                                    @if ($picture['caption'])
                                                                        <p class="text-secondary mb-0">{{ $picture['caption'] }}</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endif
                                                @endforeach
                                            </div>
                                        @endif
                                        @if ($section->description)
                                            <div class="role-overview rounded-3 p-3 p-md-4 mb-4">
                                                <h4 class="section-heading mb-2">Role Overview</h4>
                                                <p class="mb-0" style="white-space: pre-line">{{ $section->description }}</p>
                                            </div>
                                        @endif

                                        <h3 class="section-heading">{{ $section->name }} Evaluation</h3>
                                        @foreach ($step['questions']->filter(fn ($question) => $question->appliesTo($section->id))->values() as $questionIndex => $question)
                                            @include('SurveyForm::partials.question', [
                                                'number' => $questionIndex + 1,
                                                'inputName' => 'evaluations[' . $section->id . '][' . $question->id . ']',
                                                'oldKey' => 'evaluations.' . $section->id . '.' . $question->id,
                                                'sectionName' => $section->name,
                                            ])
                                        @endforeach
                                    </div>
                                @endforeach
                            @else
                                <h3 class="section-heading">{{ $department ? $department->name . ' Evaluation' : 'General Questions' }}</h3>
                                @foreach ($step['questions']->values() as $questionIndex => $question)
                                    @include('SurveyForm::partials.question', [
                                        'number' => $questionIndex + 1,
                                        'inputName' => 'answers[' . $question->id . ']',
                                        'oldKey' => 'answers.' . $question->id,
                                        'sectionName' => $department?->name ?? 'the services',
                                    ])
                                @endforeach
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach

            {{-- Review --}}
            <div class="col-12 survey-step" data-step="{{ $steps->count() + 1 }}" data-title="Review" hidden>
                <div class="card rounded-3">
                    <div class="card-header">
                        <h3 class="card-title"><i class="ti ti-checklist me-2"></i>Review &amp; Submit</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-secondary">
                            Check that every department is complete. You can go back to any step to change your answers
                            before submitting.
                        </p>
                        <div class="table-responsive border rounded mb-3">
                            <table class="table table-vcenter mb-0" id="reviewTable">
                                <thead>
                                    <tr class="mdka-bg-gray-50">
                                        <th class="text-capitalize fs-5 py-3">Department</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">Sections Evaluated</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">Answered</th>
                                        <th class="text-capitalize fs-5 py-3 text-center">Status</th>
                                        <th class="py-3"></th>
                                    </tr>
                                </thead>
                                <tbody></tbody>
                            </table>
                        </div>
                        <div class="alert alert-azure mb-0" role="alert">
                            <div class="d-flex gap-2">
                                <i class="ti ti-shield-lock fs-2"></i>
                                <div>
                                    <strong>Your privacy is protected.</strong> We only record <em>that</em> you completed
                                    the survey. Your answers are stored separately without your name and can no longer be
                                    changed after submission.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Navigation --}}
            <div class="col-12 survey-nav">
                <div class="card rounded-3 shadow-sm">
                    <div class="card-body d-flex justify-content-between align-items-center gap-2 p-2">
                        <x-global.btn-detail label="Previous" color="blue" size="sm" outline="true" id="btnPrevious">
                            <x-slot:icon><i class="ti ti-chevron-left"></i></x-slot:icon>
                        </x-global.btn-detail>
                        <div class="small text-secondary d-none d-md-block" id="draftStatus">
                            @if ($draft)
                                <i class="ti ti-cloud-check me-1"></i>Draft saved {{ $draft['saved_label'] }}
                            @else
                                <i class="ti ti-device-floppy me-1"></i>Not saved yet. Use Save as Draft to continue later.
                            @endif
                        </div>
                        <div class="d-flex gap-2">
                            <x-global.btn-detail label="Save as Draft" color="azure" size="sm" outline="true" id="btnSaveDraft">
                                <x-slot:icon><i class="ti ti-device-floppy"></i></x-slot:icon>
                            </x-global.btn-detail>
                            <x-global.btn-detail label="Next" color="blue" size="sm" id="btnNext" iconPosition="right">
                                <x-slot:icon><i class="ti ti-chevron-right"></i></x-slot:icon>
                            </x-global.btn-detail>
                            <x-global.btn-detail label="Submit Survey" color="green" size="sm" id="btnSubmit"
                                class="d-none" iconPosition="right">
                                <x-slot:icon><i class="ti ti-send"></i></x-slot:icon>
                            </x-global.btn-detail>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('javascript')
    <script>
        (function() {
            const form = $('#surveyForm');
            const steps = $('.survey-step');
            const indicators = $('#surveySteps .mcg-step');
            const lastStep = steps.length - 1;
            const draftKey = @json('survey-draft:' . $survey->id . ':' . auth()->id());
            const urlDraft = @json(route('survey-form.draft', $survey));
            const serverDraft = @json($draft);
            const hasServerErrors = @json($errors->any());
            let currentStep = 0;

            const stepPanel = (index) => steps.filter(`[data-step="${index}"]`);
            const stepIndexOf = (element) => parseInt($(element).closest('.survey-step').data('step'), 10);
            const sectionPanelsOf = (index) => stepPanel(index).find('.section-panel');
            const visibleSectionOf = (index) => sectionPanelsOf(index).filter((_, panel) => !panel.hasAttribute('hidden'));

            function isAnswered(question) {
                const element = $(question);

                if (element.data('type') === 'rating') {
                    return element.find('input[type="radio"]:checked').length > 0;
                }

                return $.trim(element.find('textarea').val() || '').length > 0;
            }

            function missingIn(scope) {
                return $(scope).find('.survey-question').filter((_, question) => $(question).data('required') == 1 && !isAnswered(question));
            }

            function isComplete(index) {
                return missingIn(stepPanel(index)).length === 0;
            }

            function firstIncompleteSection(index) {
                return sectionPanelsOf(index).filter((_, panel) => missingIn(panel).length > 0).first();
            }

            function showSection(index, sectionId) {
                sectionPanelsOf(index).each(function() {
                    $(this).prop('hidden', String($(this).data('section-id')) !== String(sectionId));
                });
                stepPanel(index).find('.section-select').val(String(sectionId));
            }

            /**
             * Mark completed sections in the section list and count them.
             */
            function refreshSectionStatus(index) {
                const panels = sectionPanelsOf(index);

                if (!panels.length) {
                    return;
                }

                let completed = 0;

                panels.each(function() {
                    const isDone = missingIn(this).length === 0;
                    const option = stepPanel(index).find(`.section-select option[value="${$(this).data('section-id')}"]`);

                    completed += isDone ? 1 : 0;
                    option.text(`${isDone ? '✓ ' : ''}${$(this).data('section-name')}`);
                });

                stepPanel(index).find('.section-progress').text(`${completed} of ${panels.length} sections evaluated`)
                    .toggleClass('bg-blue-lt', completed < panels.length)
                    .toggleClass('bg-green-lt', completed === panels.length);
            }

            function updateProgress() {
                const questions = form.find('.survey-question');
                const answered = questions.filter((_, question) => isAnswered(question)).length;
                const percentage = questions.length ? Math.round(answered / questions.length * 100) : 0;

                $('#surveyProgress').css('width', percentage + '%').attr('aria-valuenow', percentage);
                $('#surveyProgressLabel').text(`${answered} of ${questions.length} answered`);
            }

            function renderReview() {
                let html = '';
                let incomplete = 0;

                for (let index = 1; index < lastStep; index++) {
                    const questions = stepPanel(index).find('.survey-question');
                    const answered = questions.filter((_, question) => isAnswered(question)).length;
                    const missing = missingIn(stepPanel(index)).length;
                    const panels = sectionPanelsOf(index);
                    const sectionsDone = panels.filter((_, panel) => missingIn(panel).length === 0).length;

                    incomplete += missing ? 1 : 0;

                    html += `<tr>
                        <td>${escapeHtml(stepPanel(index).data('title'))}</td>
                        <td class="text-center">${panels.length ? `${sectionsDone} / ${panels.length}` : '&mdash;'}</td>
                        <td class="text-center">${answered} / ${questions.length}</td>
                        <td class="text-center">${missing
                            ? `<span class="status status-red"><span class="status-dot"></span>${missing} required missing</span>`
                            : '<span class="status status-green"><span class="status-dot"></span>Complete</span>'}</td>
                        <td class="text-end"><a href="#" class="review-edit" data-step="${index}">Edit</a></td>
                    </tr>`;
                }

                $('#reviewTable tbody').html(html);
                $('#btnSubmit').prop('disabled', incomplete > 0);
            }

            function showStep(index) {
                currentStep = Math.max(0, Math.min(index, lastStep));

                steps.attr('hidden', true);
                stepPanel(currentStep).removeAttr('hidden');

                indicators.each(function(position) {
                    $(this)
                        .toggleClass('active', position === currentStep)
                        .toggleClass('passed', position < currentStep && isComplete(position));
                });

                $('#btnPrevious').prop('disabled', currentStep === 0);
                $('#btnNext').toggleClass('d-none', currentStep === lastStep);
                $('#btnSubmit').toggleClass('d-none', currentStep !== lastStep);

                if (currentStep === lastStep) {
                    renderReview();
                }

                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            }

            /**
             * Highlight the unanswered required questions of a scope.
             */
            function validateScope(scope, message) {
                const missing = missingIn(scope);

                $('.survey-question').removeClass('has-error');
                missing.addClass('has-error');

                if (missing.length) {
                    showError([message], 'Incomplete evaluation');

                    return false;
                }

                return true;
            }

            /**
             * Open a step on its first unfinished section and highlight what is missing.
             */
            function revealIncomplete(index) {
                showStep(index);

                const section = firstIncompleteSection(index);

                if (section.length) {
                    showSection(index, section.data('section-id'));
                    validateScope(section, `Please evaluate ${section.data('section-name')} before continuing.`);

                    return;
                }

                validateScope(stepPanel(index), 'Please answer every required question (*) before continuing.');
            }

            function firstInvalidStep(upTo) {
                for (let index = 0; index < upTo; index++) {
                    if (!isComplete(index)) {
                        return index;
                    }
                }

                return null;
            }

            /**
             * Answers currently filled in, as name/value pairs.
             */
            function answerFields() {
                return form.serializeArray()
                    .filter(({
                        name
                    }) => name.startsWith('answers[') || name.startsWith('evaluations['));
            }

            /**
             * Keep a copy of the answers on this device (automatic, between server drafts).
             */
            function saveLocalDraft(savedAt = new Date().toISOString()) {
                try {
                    const values = {};

                    answerFields().forEach(({
                        name,
                        value
                    }) => values[name] = value);

                    localStorage.setItem(draftKey, JSON.stringify({
                        savedAt,
                        step: currentStep,
                        values,
                    }));
                } catch (error) {
                    // Storage unavailable (e.g. private mode): continue without the local copy.
                }
            }

            function readLocalDraft() {
                try {
                    const stored = JSON.parse(localStorage.getItem(draftKey) || 'null');

                    return stored && stored.values ? stored : null;
                } catch (error) {
                    localStorage.removeItem(draftKey);

                    return null;
                }
            }

            /**
             * Fill the form from the newest draft (saved on the server or kept on
             * this device) and return the step to resume on.
             */
            function restoreDraft() {
                if (hasServerErrors) {
                    return 0;
                }

                const local = readLocalDraft();
                const localIsNewer = local && serverDraft ?
                    Date.parse(local.savedAt) > Date.parse(serverDraft.saved_at) :
                    !!local;
                const source = localIsNewer ? local : serverDraft;

                if (!source) {
                    return 0;
                }

                Object.entries(source.values || {}).forEach(([name, value]) => {
                    const field = form.find(`[name="${CSS.escape(name)}"]`);

                    if (field.is(':radio')) {
                        field.filter(`[value="${CSS.escape(String(value))}"]`).prop('checked', true);
                    } else {
                        field.val(value);
                    }
                });

                return parseInt(source.step, 10) || 0;
            }

            $('#btnSaveDraft').on('click', function() {
                const button = $(this).prop('disabled', true);

                $.ajax({
                        url: urlDraft,
                        type: 'POST',
                        dataType: 'json',
                        data: $.param([...answerFields(), {
                            name: 'step',
                            value: currentStep
                        }]),
                    })
                    .done((response) => {
                        toastSuccess(response.message);
                        $('#draftStatus').html(`<i class="ti ti-cloud-check me-1"></i>Draft saved ${escapeHtml(response.saved_label)}`);
                        saveLocalDraft(response.saved_at);
                    })
                    .fail(showAjaxError)
                    .always(() => button.prop('disabled', false));
            });

            // Next: within a department, go through every section first.
            $('#btnNext').on('click', () => {
                const current = visibleSectionOf(currentStep);

                if (current.length) {
                    if (!validateScope(current, `Please answer every required question (*) of ${current.data('section-name')} before continuing.`)) {
                        return;
                    }

                    const next = firstIncompleteSection(currentStep);

                    if (next.length) {
                        showSection(currentStep, next.data('section-id'));
                        toastSuccess(`${current.data('section-name')} evaluated. Please continue with ${next.data('section-name')}.`);

                        // Keep the section list visible below the sticky top bar.
                        const choice = stepPanel(currentStep).find('.section-choice')[0];
                        window.scrollTo({
                            top: choice.getBoundingClientRect().top + window.scrollY - 90,
                            behavior: 'smooth',
                        });

                        return;
                    }
                } else if (!validateScope(stepPanel(currentStep), 'Please answer every required question (*) before continuing.')) {
                    return;
                }

                showStep(currentStep + 1);
            });

            $('#btnPrevious').on('click', () => showStep(currentStep - 1));

            indicators.on('click', function() {
                const target = parseInt($(this).data('step-target'), 10);
                const blocking = firstInvalidStep(target);

                if (target > currentStep && blocking !== null) {
                    revealIncomplete(blocking);

                    return;
                }

                showStep(target);
            });

            $(document).on('click', '.review-edit', function(event) {
                event.preventDefault();
                showStep(parseInt($(this).data('step'), 10));
            });

            form.on('change', '.section-select', function() {
                $('.survey-question').removeClass('has-error');
                showSection(stepIndexOf(this), $(this).val());
            });

            form.on('change', 'input[type="radio"]', function() {
                $(this).closest('.survey-question').removeClass('has-error');
            });

            form.on('input', 'textarea', function() {
                $(this).closest('.survey-question').find('.char-counter').text($(this).val().length);
            });

            form.on('change input', 'input, textarea', debounce(() => {
                steps.each((index) => refreshSectionStatus(index));
                updateProgress();
                saveLocalDraft();
            }, 300));

            form.on('submit', (event) => event.preventDefault());

            $('#btnSubmit').on('click', function() {
                const blocking = firstInvalidStep(lastStep);

                if (blocking !== null) {
                    revealIncomplete(blocking);

                    return;
                }

                Swal.fire({
                    title: 'Submit your responses?',
                    text: 'Your answers are stored anonymously and cannot be changed after submission.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonText: '<i class="ti ti-send"></i> Submit',
                    cancelButtonText: '<i class="ti ti-x"></i> Review again',
                    reverseButtons: true,
                }).then((result) => {
                    if (result.isConfirmed) {
                        $('#btnSubmit').prop('disabled', true);
                        form[0].submit();
                    }
                });
            });

            const resumeStep = restoreDraft();
            form.find('textarea').trigger('input');
            steps.each((index) => refreshSectionStatus(index));
            updateProgress();

            if (hasServerErrors) {
                const error = $('.invalid-feedback').first();
                const errorSection = error.closest('.section-panel');

                showStep(stepIndexOf(error) || 0);

                if (errorSection.length) {
                    showSection(currentStep, errorSection.data('section-id'));
                }
            } else {
                // Resume a draft on its step, but never beyond the first unfinished one.
                const blocking = firstInvalidStep(resumeStep);

                showStep(blocking === null ? resumeStep : blocking);

                const section = firstIncompleteSection(currentStep);

                if (section.length) {
                    showSection(currentStep, section.data('section-id'));
                }
            }
        })();
    </script>
@endpush
