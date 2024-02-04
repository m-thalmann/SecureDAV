<x-layout :title="__('Server Error')">
    <x-error-content
        :description="__('An error ocurred while processing your request. Please try again or contact the system administrator.')"
        errorCode="500"
    >
        <code>
            {{ get_class($exception) }}:
            {{ $exception->getMessage() }}
        </code>
    </x-error-content>
</x-layout>