{{-- The top of every e-mail: the company logos (embedded, see mail.survey-reminder) above the name of the application. --}}
@props(['url', 'logos' => []])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@foreach (array_filter($logos) as $company => $logo)
<img src="{{ $logo }}" class="header-logo" alt="{{ $company }}">
@endforeach
<span class="header-name">{!! $slot !!}</span>
</a>
</td>
</tr>
