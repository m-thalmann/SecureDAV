<div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-transparent">
    <div>
        <x-application-logo class="w-20 h-20 block mb-2 mx-auto" />
        <span class="block text-3xl text-center">
            @isset($title)
                {{ $title }}
            @else      
                {{ config('app.name', 'SecureDAV') }}
            @endisset
        </span>
    </div>

    <div class="w-full sm:max-w-md mt-6 px-6 py-4 bg-white shadow-md overflow-hidden sm:rounded-lg dark:bg-gray-800">
        {{ $slot }}
    </div>

    @isset($underCard)
        <div class="mt-2">
            {{ $underCard }}
        </div>
    @endisset
</div>
