<x-layout :title="__('Page expired')">
    <x-error-content
        :description="__('The page has expired. Please refresh and try again.')"
        errorCode="419"
    />
</x-layout>