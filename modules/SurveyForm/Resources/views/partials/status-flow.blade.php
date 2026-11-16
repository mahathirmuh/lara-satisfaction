{{-- Where the signed-in person stands with a survey: Not Started -> In Progress (Saved as draft) -> Completed, the current step highlighted. Expects $status (App\Enums\ParticipationStatus) and $savedDraft (bool); optional $id and $class. --}}
@php($isCompleted = $status === \App\Enums\ParticipationStatus::Completed)
<x-global.steps :id="$id ?? null" :class="'survey-status-flow ' . ($class ?? '')"
    :aria-label="__('Status: :status', ['status' => $status->label()])">
    @foreach (\App\Enums\ParticipationStatus::cases() as $step)
        <x-global.steps.step :number="$step->step()" :label="$step->label()"
            :passed="$isCompleted || $step->step() < $status->step()" :active="$step === $status"
            :note="$step === $status && $step === \App\Enums\ParticipationStatus::InProgress && $savedDraft ? __('Saved as draft') : null" />
    @endforeach
</x-global.steps>
