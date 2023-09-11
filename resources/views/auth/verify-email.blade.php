<x-guest-layout :title="__('Verify email')">
    <x-auth-card>
        <div class="text-left text-sm">
            {{ __('It seems like you haven\'t yet verified your email by clicking on the link we just emailed to you. If you didn\'t receive the email, we will gladly send you another.') }}
        </div>

        {{-- The response of the "verification.send" endpoint can't be modified --}}
        @if (session('status') == 'verification-link-sent')
            <x-session-message :message="\App\View\Helpers\SessionMessage::success('A new verification link has been sent to the email address you\'ve provided.')" class="my-3"></x-session-message>
        @endif

        <div class="mt-4 flex flex-col sm:flex-row items-center justify-between gap-4 w-full">
            <form method="POST" action="{{ route('verification.send') }}">
                @csrf

                <input type="submit" value="{{ __('Resend Verification Email') }}" class="btn btn-primary">
            </form>

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <input type="submit" value="{{ __('Log Out') }}" class="btn btn-neutral">
            </form>
        </div>
    </x-auth-card>

    <a href="{{ route('settings.profile.show') }}" class="link link-hover mt-2">{{ __('Go to settings') }} &rarr;</a>
</x-guest-layout>