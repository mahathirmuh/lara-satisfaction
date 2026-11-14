@props([
    'name' => 'search',
    'placeholder' => 'Search…',
])

<div class="input-icon">
    <span class="input-icon-addon">
        <i class="ti ti-search"></i>
    </span>
    <input type="text" name="{{ $name }}" autocomplete="off" placeholder="{{ $placeholder }}"
        {{ $attributes->merge(['class' => 'form-control rounded-3']) }} />
</div>
