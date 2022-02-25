@props(['href' => null, 'danger' => false, 'customColor' => null])

<{{$href === null ? "button" : "a"}}
    {{ $href !== null ? "href=" . $href : ""}}
    {{
        $attributes->merge([
            'type' => $href === null ? 'submit' : null,
            'class' =>
                'cursor-pointer inline-flex items-center px-4 py-2 border border-transparent
                rounded-md font-semibold text-xs text-white uppercase tracking-widest focus:outline-none
                focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ' .
                ($customColor !== null ?
                    $customColor :
                    ($danger ?
                        'bg-red-600 hover:bg-red-800 active:bg-red-900 focus:border-red-900' :
                        'bg-gray-800 hover:bg-gray-700 active:bg-gray-900 focus:border-gray-900 dark:bg-gray-600'
                    )
                )
        ])
    }}
>
    {{ $slot }}
</{{$href === null ? "button" : "a"}}>
