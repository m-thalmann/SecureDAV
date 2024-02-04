<x-layout :title="__('Service unavailable')">
    <x-error-content
        :description="__('The server is currently unavailable. Please try again later.')"
        errorCode="503"
    />
</x-layout>