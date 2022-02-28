@props(['disabled' => false])

<input
    {{ $disabled ? 'disabled' : '' }}
    {!! $attributes->merge(
        [
            'class' => 
                'rounded-md shadow-sm border-gray-300 focus:border-gray-300 focus:ring
                focus:ring-gray-200 focus:ring-opacity-50 disabled:text-gray-400
                dark:border-none dark:bg-gray-700 dark:focus:ring-gray-600 dark:text-white
                dark:disabled:text-gray-500'
        ]
    ) !!}
>
