@props(['message'])

@if ($message)
    <div {{ $attributes->merge(['class' => 'alert text-left alert-' . $message->type]) }}>
        <i class="{{ $message->getIcon() }}"></i>
        {{ $message->message }}
    </div>
@endif
