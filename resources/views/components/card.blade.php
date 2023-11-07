@props([
    'dialog' => false
])

<div {{ $attributes->merge(['class' => 'card bg-base-200 shadow-lg max-sm:rounded-none' . ($dialog ? ' md:w-2/3 md:mx-auto' : '')]) }}>
    <div class="card-body">
        @isset ($title)
            <h2 {{ $title->attributes->merge(['class' => 'card-title flex gap-4 items-center']) }}>
                @if ($title->attributes->has('icon'))
                    <i class="{{ $title->attributes->get('icon') }}"></i>
                @endif

                <div>
                    <span class="flex gap-2 items-center">
                        {{ $title }}
    
                        @if ($title->attributes->has('amount'))
                            <small class="font-normal">({{ $title->attributes->get('amount') }})</small>
                        @endif
                    </span>

                    @isset ($subtitle)
                        <small {{ $title->attributes->merge(['class' => 'text-sm font-normal text-base-content/60 flex gap-2 items-center']) }}>
                            @if ($subtitle->attributes->has('icon'))
                                <i class="{{ $subtitle->attributes->get('icon') }}"></i>
                            @endif

                            <span>
                                {{ $subtitle }}
                            </span>
                        </small>
                    @endisset
                </div>

                @isset ($titleSuffix)
                    <span class="flex-1"></span>

                    {{ $titleSuffix }}
                @endisset
            </h2>
        @endisset

        {{ $slot }}

        @isset ($actions)
            <div {{ $actions->attributes->merge(['class' => 'card-actions justify-end']) }}>
                {{ $actions }}
            </div>
        @endisset
    </div>
</div>