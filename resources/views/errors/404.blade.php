<x-layout :title="__('Not Found')">
    <x-error-content
        :description="__('The requested resource could not be found.')"
        errorCode="404"
    />
</x-layout>