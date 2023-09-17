<x-app-layout :title="__('Profile')">
    <div class="space-y-16">
        @include('settings.profile.partials.update-profile-information-form')
        @include('settings.profile.partials.update-password-form')
        @include('settings.profile.partials.two-factor-authentication-form')
        @include('settings.profile.partials.manage-browser-sessions-form')
        @include('settings.profile.partials.delete-account-form')
    </div>
</x-app-layout>