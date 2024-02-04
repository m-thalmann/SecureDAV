<x-layout :title="__('Unauthorized')">
    <x-error-content
        :description="__('You are not authorized to access this page. Please log in and try again.')"
        errorCode="401"
    />
</x-layout>