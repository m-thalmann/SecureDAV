@extends('admin._layout')

@section('content')
    <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 md:grid-cols-2 lg:grid-cols-3 mb-8">
        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountUsers }}</span>
            <span class="text-center"><i class="fa-solid fa-users"></i> {{ __('Users') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountFiles }}</span>
            <span class="text-center"><i class="fa-solid fa-file"></i> {{ __('Files') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountVersions }}</span>
            <span class="text-center"><i class="fa-solid fa-clock-rotate-left"></i> {{ __('Versions') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $fileSize }}</span>
            <span class="text-center"><i class="fa-solid fa-hard-drive"></i> {{ __('Total physical size') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountWebDavUsers }}</span>
            <span class="text-center"><i class="fa-solid fa-user-group"></i> {{ __('WebDav users') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountConfiguredBackups }}</span>
            <span class="text-center"><i class="fa-solid fa-rotate"></i> {{ __('Configured backups') }}</span>
        </div>
    </div>

    <x-card>
        <x-slot name="title" icon="fa-solid fa-server">{{ __('System information') }}</x-slot>

        <div class="mt-4 overflow-auto">
            <table class="w-full">
                <tbody>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('Environment') }}</td>
                        <td>{{ $settings['environment'] }}</td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('Debug mode') }}</td>
                        <td>
                            <input type="checkbox" @checked($settings['debug']) class="checkbox checkbox-sm checkbox-secondary cursor-default" onclick="return false;" />
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('Default timezone') }}</td>
                        <td>{{ $settings['default_timezone'] }}</td>
                    </tr>
                    <tr>
                        <td colspan="2"><div class="divider"></div></td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('Registration enabled') }}</td>
                        <td>
                            <input type="checkbox" @checked($settings['registration_enabled']) class="checkbox checkbox-sm checkbox-secondary cursor-default" onclick="return false;" />
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('Email verification enabled') }}</td>
                        <td>
                            <input type="checkbox" @checked($settings['email_verification_enabled']) class="checkbox checkbox-sm checkbox-secondary cursor-default" onclick="return false;" />
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2"><div class="divider"></div></td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('WebDav CORS enabled') }}</td>
                        <td>
                            <input type="checkbox" @checked($settings['webdav_cors_enabled']) class="checkbox checkbox-sm checkbox-secondary cursor-default" onclick="return false;" />
                        </td>
                    </tr>
                    <tr>
                        <td class="font-bold w-0 whitespace-nowrap pr-8">{{ __('WebDav CORS Allowed Origins') }}</td>
                        <td>{{ $settings['webdav_cors_allowed_origins'] }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </x-card>
@endsection
