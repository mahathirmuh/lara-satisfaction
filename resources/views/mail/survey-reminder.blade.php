{{--
    The survey e-mails: the reminder (App\Notifications\SurveyDeadlineReminder) and the thank-you after completing a
    survey (App\Notifications\SurveyCompleted). The greeting, what it is about, the survey at a glance (the recipient's
    own status, or when it was submitted), the button, and who sends it. The company logos are embedded in the e-mail
    (only in its HTML part), so mail clients show them without loading pictures from elsewhere.
--}}
@php
    $logos = isset($message) && $message instanceof \Illuminate\Mail\Message ? [
        'Merdeka Copper Gold' => $message->embed(public_path('assets/img/branding/email-mcg.png')),
        'Merdeka Tsingshan Indonesia' => $message->embed(public_path('assets/img/branding/email-mti.png')),
        'Merdeka Battery Materials' => $message->embed(public_path('assets/img/branding/email-mbm.png')),
    ] : [];
@endphp
<x-mail::message :logos="$logos">
# {{ $greeting }}

@foreach ($introLines as $line)
{{ $line }}

@endforeach
<x-mail::panel>
**{{ $surveyTitle }}**<br>
@foreach ($details as $detail)
{{ $detail }}<br>
@endforeach
</x-mail::panel>

<x-mail::button :url="$actionUrl" color="primary">
{{ $actionText }}
</x-mail::button>

@foreach ($outroLines as $line)
{{ $line }}

@endforeach
{{ __('Regards,') }}<br>
{{ $signature }}

<x-slot:subcopy>
@lang(
    "If you're having trouble clicking the \":actionText\" button, copy and paste the URL below\n".
    'into your web browser:',
    ['actionText' => $actionText]
) <span class="break-all">[{{ $displayableActionUrl }}]({{ $actionUrl }})</span>
</x-slot:subcopy>
</x-mail::message>
