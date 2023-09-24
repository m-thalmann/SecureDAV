@props([
    'name' => null,
    'type' => 'text',
    'inputClass' => 'input input-md',
    'class' => null,
    'errorBag' => 'default'
])

<input
    id="{{ $name }}"
    type="{{ $type }}"
    name="{{ $name }}"
    @class([
        $inputClass,
        $class,
        'w-full',
        'input-error' => $errors->getBag($errorBag)->get($name)
    ])
    {{ $attributes }} />