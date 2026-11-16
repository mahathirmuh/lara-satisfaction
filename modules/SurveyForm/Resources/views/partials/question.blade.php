{{-- One wizard question. Expects $question, $number, $inputName (e.g. "evaluations[5][12]"), $oldKey (e.g. "evaluations.5.12"), $sectionName (replaces "{section}"), $commentMaxLength and $feedbackGuidelines. Standard question texts are shown in the interface language (SurveyQuestion::text()). --}}
<div class="survey-question py-3" data-question-id="{{ $question->id }}" data-required="{{ $question->is_required ? 1 : 0 }}"
    data-type="{{ $question->isRating() ? 'rating' : 'text' }}">
    <div class="question-text fw-bold mb-2">
        {{ $number }}. {{ $question->textHtml($sectionName) }}
        @if ($question->is_required)
            <span class="text-danger" title="{{ __('Required') }}">*</span>
        @endif
    </div>
    @if ($question->help_text)
        <div class="text-secondary small mb-2">{{ $question->help_text }}</div>
    @endif

    @if ($question->isRating())
        @php($scale = $question->type->scale())
        <div class="form-selectgroup form-selectgroup-boxes d-flex flex-wrap gap-2" role="radiogroup"
            aria-label="{{ $number }}. {{ $question->textFor($sectionName) }}">
            @foreach ($scale['levels'] as $value => $level)
                <label class="form-selectgroup-item flex-fill rating-option">
                    <input type="radio" name="{{ $inputName }}" value="{{ $value }}" class="form-selectgroup-input"
                        @checked((string) old($oldKey) === (string) $value)>
                    <span class="form-selectgroup-label d-flex flex-column align-items-center p-2">
                        <span class="fs-2 fw-bold">{{ $value }}</span>
                        <span class="rating-scale-label text-secondary">{{ $level['label'] }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        <div class="rating-description rounded-2 mt-2 p-3 small">
            <div class="fw-bold mb-1">{{ __('Rating Description - :scale:', ['scale' => $scale['title']]) }}</div>
            @foreach (array_reverse($scale['levels'], true) as $value => $level)
                <div>{{ $value }} = <strong>{{ $level['label'] }}:</strong> {{ $level['description'] }}</div>
            @endforeach
        </div>
    @else
        {{-- Answer on the left, writing guidelines on the right (stacked on small screens) --}}
        <div class="row g-3">
            <div class="{{ $feedbackGuidelines ? 'col-lg-8' : 'col-12' }} d-flex flex-column">
                <textarea name="{{ $inputName }}" class="form-control flex-grow-1" rows="5" maxlength="{{ $commentMaxLength }}"
                    placeholder="{{ $question->is_required
                        ? __('Share any additional comments, suggestions for improvement, or specific feedback about the service…')
                        : __('Share any additional comments, suggestions for improvement, or specific feedback about the service (optional)…') }}">{{ old($oldKey) }}</textarea>
                <div class="small text-secondary mt-1">
                    <span class="char-counter">0</span>/{{ __(':max characters', ['max' => $commentMaxLength]) }}
                </div>
            </div>
            @if ($feedbackGuidelines)
                <div class="col-lg-4">
                    <div class="feedback-guidelines rounded-2 p-3 small h-100">
                        <div class="fw-bold mb-1"><i class="ti ti-bulb me-1"></i>{{ __('Feedback Guidelines:') }}</div>
                        <ul class="mb-0 ps-3">
                            @foreach ($feedbackGuidelines as $guideline)
                                <li>{{ __($guideline) }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    @endif

    @error($oldKey)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
