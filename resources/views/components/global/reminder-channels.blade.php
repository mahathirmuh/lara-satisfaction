{{-- How a reminder sent by hand goes: the notification in the application and the e-mail, both ticked. The e-mail says
     where it comes from, or that e-mails are only written to the log for now. --}}
@props([
    'channels' => \App\Enums\ReminderChannel::options(),
    'emailSender' => ['address' => '', 'name' => '', 'delivered' => true],
    'idPrefix' => 'reminder',
])

<fieldset {{ $attributes->merge(['class' => 'mb-3']) }}>
    <legend class="form-label required">{{ __('Send by') }}</legend>
    @foreach ($channels as $value => $label)
        <label class="form-check mb-1" for="{{ $idPrefix }}Channel-{{ $value }}">
            <input class="form-check-input" type="checkbox" name="channels[]" value="{{ $value }}"
                id="{{ $idPrefix }}Channel-{{ $value }}" checked>
            <span class="form-check-label">
                <i class="ti {{ $value === \App\Enums\ReminderChannel::Email->value ? 'ti-mail' : 'ti-bell' }} me-1" aria-hidden="true"></i>{{ $label }}
                @if ($value === \App\Enums\ReminderChannel::Email->value)
                    @if ($emailSender['delivered'])
                        <span class="d-block small text-secondary">{{ __('From :sender', ['sender' => $emailSender['address']]) }}</span>
                    @else
                        <span class="d-block small text-warning">{{ __('Only written to the log for now (MAIL_MAILER)') }}</span>
                    @endif
                @endif
            </span>
        </label>
    @endforeach
</fieldset>
