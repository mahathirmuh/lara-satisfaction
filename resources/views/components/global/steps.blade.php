@pushOnce('style')
    <style>
        .mcg-steps-container {
            overflow-x: auto;
            padding-bottom: .25rem;
        }

        .mcg-steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: nowrap;
            gap: 14px;
            min-width: max-content;
        }

        .mcg-step-divider {
            border: 1px dashed #000000;
            flex: 1 1 2rem;
            min-width: 2rem;
            margin: auto;
            opacity: .35;
        }
    </style>
@endPushOnce

<div {{ $attributes->merge(['class' => 'mcg-steps-container']) }}>
    <div class="mcg-steps">
        {{ $slot }}
    </div>
</div>

@pushOnce('javascript')
    <script>
        // Draw a divider between consecutive steps of every stepper.
        $('.mcg-steps').each(function() {
            const steps = $(this).children('.mcg-step');

            steps.each(function(index) {
                if (index < steps.length - 1) {
                    $(this).after($('<hr>').addClass('mcg-step-divider'));
                }
            });
        });
    </script>
@endPushOnce
