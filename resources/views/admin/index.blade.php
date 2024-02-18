@extends('admin._layout')

@section('content')
    <div class="grid grid-cols-1 gap-6 px-4 sm:px-0 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
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
            <span class="text-primary text-4xl font-light">{{ $amountWebDavUsers }}</span>
            <span class="text-center"><i class="fa-solid fa-user-group"></i> {{ __('WebDav users') }}</span>
        </div>

        <div class="bg-base-200 px-4 py-6 rounded-md flex flex-col gap-2 items-center">
            <span class="text-primary text-4xl font-light">{{ $amountConfiguredBackups }}</span>
            <span class="text-center"><i class="fa-solid fa-rotate"></i> {{ __('Configured backups') }}</span>
        </div>
    </div>
@endsection
