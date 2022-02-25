<x-app-layout
    :title="__('Dashboard')"
    :header="['icon' => 'fa-solid fa-house', 'items' => [__('Dashboard')]]"
>
    <x-content-card>
        <ul>
            <li><strong>User:</strong> {{ Auth::user()->email }}</li>
            <li><strong>Files:</strong> {{ count(Auth::user()->files) }}</li>
        </ul>
    </x-content-card>
</x-app-layout>
