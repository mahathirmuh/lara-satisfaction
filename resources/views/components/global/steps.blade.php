@props(['vertical' => false])

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

        /* Vertical stepper: steps stacked, the label beside its number. */
        .mcg-steps-container.vertical {
            overflow-x: visible;
        }

        .mcg-steps-container.vertical .mcg-steps {
            flex-direction: column;
            align-items: flex-start;
            gap: 0;
            min-width: 0;
        }

        .mcg-steps-container.vertical .mcg-step {
            flex-direction: row;
            gap: 10px;
        }

        .mcg-steps-container.vertical .mcg-step-text {
            align-items: flex-start;
        }

        .mcg-steps-container.vertical .mcg-step-divider {
            flex: none;
            width: 0;
            min-width: 0;
            height: 20px;
            margin: 4px 0 4px 17px;
            border: 0;
            border-left: 1px dashed #000000;
        }
    </style>
@endPushOnce

<div {{ $attributes->merge(['class' => 'mcg-steps-container' . ($vertical ? ' vertical' : '')]) }}>
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
