<div {{ $attributes->merge(['class' => 'w-full flex flex-col gap-4 md:grid md:grid-cols-3 md:gap-6']) }}>
    <div class="md:col-span-1 flex justify-between">
        <div class="px-4 sm:px-0">
            <h3 class="text-lg text-base-content">{{ $title }}</h3>

            <p class="text-sm text-base-content/60 font-light mt-1">
                {{ $description }}
            </p>
        </div>
    </div>

    <x-card class="md:col-span-2">
        {{ $slot }}

        @isset($actions)
            <x-slot:actions :class="$actions->attributes->get('class')">
                {{ $actions }}
            </x-slot:actions>
        @endisset
    </x-card>
</div>
