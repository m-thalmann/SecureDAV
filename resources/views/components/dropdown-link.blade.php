<a
    {{
        $attributes->merge([
            'class' => 'block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100
                        transition duration-150 ease-in-out dark:bg-transparent dark:text-white dark:hover:bg-gray-700
                        dark:hover:text-white dark:focus:bg-gray-500'
        ])
    }}
>{{ $slot }}</a>
