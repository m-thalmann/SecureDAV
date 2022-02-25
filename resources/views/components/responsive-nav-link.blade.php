@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block pl-3 pr-4 py-2 border-l-4 border-orange-400 text-base font-medium text-orange-700
               bg-orange-50 focus:outline-none focus:text-orange-800 focus:bg-orange-100 focus:border-orange-700
               transition duration-150 ease-in-out dark:text-orange-600 dark:bg-gray-800 dark:border-orange-500
               dark:focus:bg-gray-600 dark:focus:text-orange-500 dark:focus:border-orange-500'
            : 'block pl-3 pr-4 py-2 border-l-4 border-transparent text-base font-medium text-gray-600 hover:text-gray-800 hover:bg-gray-50
               hover:border-gray-300 focus:outline-none focus:text-gray-800 focus:bg-gray-50 focus:border-gray-300 transition duration-150
               ease-in-out dark:text-gray-300 dark:hover:bg-gray-800 dark:hover:text-white dark:focus:bg-gray-600 dark:focus:text-white';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
