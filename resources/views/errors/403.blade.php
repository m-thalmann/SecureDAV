<x-layout :title="__('Forbidden')">
    <x-error-content
        :description="__('You are not authorized to access this page. Please contact the system administrator.')"
        errorCode="403"
    />
</x-layout>