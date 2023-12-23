<x-form-field name="method" class="md:w-2/3">
    <x-slot name="label">{{ __('Method') }}</x-slot>

    <select name="method" class="select">
        <option value="PUT" @selected(isset($configuration) ? $configuration->config['method'] === 'PUT' : old('method') === 'PUT')>PUT</option>
        <option value="POST" @selected(isset($configuration) ? $configuration->config['method'] === 'POST' : old('method') === 'POST')>POST</option>
    </select>
</x-form-field>

<x-form-field name="targetUrl" class="md:w-2/3">
    <x-slot name="label">{{ __('Target URL') }}</x-slot>

    <x-input name="targetUrl" type="url" :value="isset($configuration) ? $configuration->config['targetUrl'] : old('targetUrl')" placeholder="https://dav.example.com/directory1" />
</x-form-field>

<x-form-field name="username" class="md:w-2/3">
    <x-slot name="label">{{ __('Username') }}</x-slot>

    <x-input name="username" :value="isset($configuration) ? $configuration->config['username'] : old('username')" placeholder="user1" />
</x-form-field>

<x-form-field name="password" class="md:w-2/3">
    <x-slot name="label">{{ __('Password') }}</x-slot>

    <x-input name="password" type="password" placeholder="123" />
</x-form-field>