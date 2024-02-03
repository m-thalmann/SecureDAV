@props([
    'data' => '',
    'inputId' => null,
    'containerClass' => ''
])

@php
    if($inputId !== null) {
        $copyAction = "document.getElementById('$inputId').select(); document.execCommand('copy')";
    }else{
        $data = Js::from($data);

        $copyAction = "navigator.clipboard.writeText($data)";
    }
@endphp

<button
    {{ $attributes->merge(['class' => !$attributes->has('plain') ? 'btn btn-circle btn-sm btn-ghost' : '']) }}
    x-data="{ success: false }"
    x-on:click="
        {!! $copyAction !!};
        success = true;
        setTimeout(() => { success = false }, 1000)
    "
>
    <template x-if="!success" class="content">
        @if ($slot->isNotEmpty())
            <div @class([$containerClass])>
                {{ $slot }}
            </div>
        @else
            <i class="fa-solid fa-copy"></i>
        @endif
    </template>

    <template x-if="success" class="success-content">
        @isset ($success)
            <div @class([$containerClass])>
                {{ $success }}
            </div>
        @else
            <i class="fa-solid fa-check text-success"></i>
        @endisset
    </template>
</button>