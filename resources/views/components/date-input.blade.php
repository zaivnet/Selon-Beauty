@props([
    'name',
    'id' => null,
    'value' => '',
    'required' => false,
    'min' => null,
    'max' => null,
    'onchange' => null,
    'wrapperClass' => '',
])

@php
    $elementId = $id ?? $name;
@endphp

<div class="ios-date-field {{ $wrapperClass }}">
    <input 
        type="date" 
        name="{{ $name }}" 
        id="{{ $elementId }}" 
        value="{{ $value }}" 
        @if($required) required @endif
        @if($min) min="{{ $min }}" @endif
        @if($max) max="{{ $max }}" @endif
        @if($onchange) onchange="{{ $onchange }}" @endif
        {{ $attributes->merge(['class' => '']) }}
    />
</div>
