@props(['snackbar'])

@if($snackbar !== null)
    @php
        $snackbarBackground = "bg-gray-700 dark:bg-gray-800";
        $snackbarForeground = "text-white";
        $snackbarIcon = "fa-solid fa-circle-info";
        $snackbarDuration = array_key_exists("duration", $snackbar) ? $snackbar["duration"] : 5000;

        switch($snackbar["type"]){
            case("error"):
                $snackbarBackground = "bg-red-600";
                $snackbarIcon = "fa-solid fa-circle-exclamation";
                break;
            case("warn"):
                $snackbarBackground = "bg-orange-500";
                $snackbarIcon = "fa-solid fa-triangle-exclamation";
                break;

            case("success"):
                $snackbarBackground = "bg-green-600";
                $snackbarIcon = "fa-solid fa-check";
                break;
        }
    @endphp

    <div
        class="fixed left-1/2 -translate-x-1/2 bottom-10 {{ $snackbarBackground }} {{ $snackbarForeground }} text-center shadow-lg py-4 px-4 rounded-md"
        @if ($snackbarDuration !== null)
            x-init="setTimeout(function() { $el.remove() }, {{ $snackbarDuration }})"
        @endif
    >
        <i class="{{ $snackbarIcon }} px-2"></i>
        <span class="mx-2 inline-block">{{ $snackbar["message"] }}</span>
        <button
            type="button"
            class="w-8 h-8 hover:bg-black hover:bg-opacity-10 rounded-full transition"
            onclick="this.parentElement.remove()"
        >
            <i class="fa-solid fa-times"></i>
        </button>
    </div>
@endif