<x-form-section id="browser-sessions">
    <x-slot name="title">
        {{ __('Browser Sessions') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Manage and log out your active sessions on other browsers and devices.') }}
    </x-slot>

    <x-slot name="form">
        {{ __('If necessary, you may log out of all of your browser sessions across all of your devices. If you feel your account has been compromised, you should also update your password.') }}

        @if($sessions !== null)
            <div class="mt-6 flex flex-col gap-4">
                @foreach ($sessions as $session)
                    <div class="flex items-center">
                        <div class="w-8 text-center">
                            @if ($session->agent->isDesktop)
                                <i class="fa-solid fa-desktop text-2xl"></i>
                            @else
                                <i class="fa-solid fa-mobile text-2xl"></i>
                            @endif
                        </div>

                        <div class="ml-3">
                            <div class="text-sm">
                                {{ $session->agent->platform }} - {{ $session->agent->browser }}
                            </div>

                            <div>
                                <div class="text-xs">
                                    <span class="opacity-60">
                                        {{ $session->ipAddress }}
                                    </span>
                                    
                                    &nbsp;

                                    @if ($session->isCurrentDevice)
                                        <span class="text-primary font-semibold">{{ __('This device') }}</span>
                                    @else
                                        {{ __('Last active') }} {{ $session->lastActive }}
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        <x-session-message :message="session('session-message[browser-sessions]')" class="mt-6"></x-session-message>

        <form method="POST" action="{{ route('settings.profile.sessions.destroy') }}" class="mt-6">
            @method('DELETE')
            @csrf

            <input type="submit" value="{{ __('Log Out All Sessions') }}" class="btn btn-neutral">
        </form>
    </x-slot>
</x-form-section>
