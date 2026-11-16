@props([
    'number' => 1,
    'label' => null,
    'active' => false,
    'passed' => false,
])

@pushOnce('style')
    <style>
        .mcg-step {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .mcg-step-number {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background-color: #ffffff;
            display: flex;
            justify-content: center;
            align-items: center;
            font-size: 12px;
            font-weight: bold;
            border: 4px solid #DBEAFE;
            color: #666666;
        }

        .mcg-step-label {
            font-size: 12px;
            font-weight: bold;
            white-space: nowrap;
            color: #666666;
        }

        .mcg-step .mcg-step-check {
            display: none;
        }

        .mcg-step.passed .mcg-step-number {
            background-color: #10B981;
            color: white;
        }

        .mcg-step.passed .mcg-step-check {
            display: inline;
        }

        .mcg-step.passed .mcg-step-digit {
            display: none;
        }

        .mcg-step.passed .mcg-step-label {
            color: #10B981;
        }

        .mcg-step.active .mcg-step-number {
            background-color: #3B82F6;
            color: white;
        }

        .mcg-step.active .mcg-step-label {
            color: #3B82F6;
        }
    </style>
@endPushOnce

<div {{ $attributes->merge(['class' => 'mcg-step' . ($passed ? ' passed' : '') . ($active && !$passed ? ' active' : '')]) }}>
    <div class="mcg-step-number">
        <i class="ti ti-check mcg-step-check"></i>
        <span class="mcg-step-digit">{{ $number }}</span>
    </div>
    <div class="mcg-step-label">{{ $label ?? __('Step :number', ['number' => $number]) }}</div>
</div>
