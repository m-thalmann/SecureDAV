@props([
    'dialog' => false,
    'collapsible' => false,
])

<div
    {{ $attributes->merge(['class' => 'card bg-base-200 shadow-lg max-sm:rounded-none' . ($dialog ? ' md:w-2/3 md:mx-auto' : '')]) }}
>
    <div class="card-body transition-[padding]" :class="collapsed ? 'py-4' : ''" x-data="{ collapsed: {{ $collapsible ? 'true' : 'false' }} }">
        @isset ($title)
            <h2 {{ $title->attributes->merge(['class' => 'card-title flex gap-4 items-center']) }}>
                @if ($collapsible)
                    <button class="btn btn-circle btn-ghost btn-xs" x-on:click="collapsed = !collapsed">
                        <i class="fa-solid fa-chevron-right" x-show="collapsed"></i>
                        <i class="fa-solid fa-chevron-down" x-show="!collapsed" x-cloak></i>
                    </button>
                @endif

                @if ($title->attributes->has('icon'))
                    <i class="{{ $title->attributes->get('icon') }}"></i>
                @endif

                <div>
                    <span class="flex gap-2 items-center">
                        {{ $title }}

                        @if ($title->attributes->has('amount'))
                            <small class="font-normal">({{ $title->attributes->get('amount') }})</small>
                        @endif

                        @isset ($collapsedTitleSuffix)
                            <small class="font-normal text-sm text-base-content/75 ml-2" x-show="collapsed" x-cloak>
                                {{ $collapsedTitleSuffix }}
                            </small>
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

        <div class="flex flex-col flex-auto gap-2" x-show="!collapsed" @if($collapsible) x-cloak @endif>
            {{ $slot }}

            @isset ($actions)
                <div {{ $actions->attributes->merge(['class' => 'card-actions justify-end']) }}>
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</div>
