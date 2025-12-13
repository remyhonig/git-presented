@props(['name' => '', 'email' => '', 'size' => 32])

@php
$hash = md5(strtolower(trim($email)));
$gravatarUrl = "https://www.gravatar.com/avatar/{$hash}?s={$size}&d=identicon";
@endphp

<div class="flex items-center space-x-2">
    <img
        src="{{ $gravatarUrl }}"
        alt="{{ $name }}"
        class="rounded-full"
        width="{{ $size }}"
        height="{{ $size }}"
    >
    <span class="text-sm" style="color: var(--text-secondary);">{{ $name }}</span>
</div>
