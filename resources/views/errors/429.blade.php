<x-layout :title="__('Too many requests')">
    <x-error-content
        :description="__('You have made too many requests in a short period of time. Please try again later.')"
        errorCode="429"
    />
</x-layout>