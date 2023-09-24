<x-form-section id="two-factor-authentication">
    <x-slot name="title">
        {{ __('Two Factor Authentication') }}
    </x-slot>

    <x-slot name="description">
        {{ __('Add additional security to your account using two factor authentication.') }}
    </x-slot>

    <x-slot name="form">
        <h3 class="text-lg font-medium">
            @if ($twoFactorEnabled)
                @if (!$twoFactorConfirmed)
                    {{ __('Finish enabling two factor authentication.') }}
                @else
                    {{ __('You have enabled two factor authentication.') }}
                @endif
            @else
                {{ __('You have not enabled two factor authentication.') }}
            @endif
        </h3>

        <p class="mt-3 text-sm">
            {{ __('When two factor authentication is enabled, you will be prompted for a secure, random token during authentication. You may retrieve this token from your phone\'s authenticator application.') }}
        </p>

        @if ($twoFactorEnabled)
            @if (!$twoFactorConfirmed)
                <p class="mt-4 text-sm font-semibold">
                    {{ __('To finish enabling two factor authentication, scan the following QR code using your phone\'s authenticator application or enter the setup key and provide the generated OTP code.') }}
                </p>

                <div class="mt-4 p-2 w-fit bg-white">
                    {!! $user->twoFactorQrCodeSvg() !!}
                </div>

                <p class="mt-4 text-sm flex items-center gap-2">
                    <strong>{{ __('Setup Key') }}:</strong> <code>{{ decrypt($user->two_factor_secret) }}</code>
                </p>

                <form method="POST" action="{{ route('two-factor.confirm') }}" id="two-factor-confirm-form">
                    @csrf

                    <x-form-field name="code" errorBag="confirmTwoFactorAuthentication" class="mt-4 sm:w-1/2">
                        <x-slot:label>{{ __('Code') }}</x-slot:label>

                        <x-input name="code" inputmode="numeric" errorBag="confirmTwoFactorAuthentication" required autocomplete="one-time-code" />
                    </x-form-field>
                </form>

            @endif

            @if (session('two-factor-confirmed') || session('two-factor-recovery-codes-regenerated'))
                <div class="mt-4 text-sm alert alert-info">
                    <i class="fa-solid fa-circle-exclamation"></i>

                    <span>
                        {{ __('Store these recovery codes in a secure location. They can be used to recover access to your account if your two factor authentication device is lost.') }}
                    </span>
                </div>

                <div class="grid gap-1 mt-4 px-4 py-4 font-mono text-sm text-center rounded-lg bg-base-300 text-base-content sm:w-fit sm:text-left">
                    @foreach (json_decode(decrypt($user->two_factor_recovery_codes), true) as $code)
                        <div>{{ $code }}</div>
                    @endforeach
                </div>
            @endif
        @endif

        <div
            @class([
                'flex gap-4 flex-col sm:flex-row',
                'mt-5' => !$twoFactorEnabled || $twoFactorConfirmed
            ])
        >
            @if (!$twoFactorEnabled)
                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf

                    <input type="submit" value="{{ __('Enable') }}" class="btn btn-neutral">
                </form>
            @else
                @if(!$twoFactorConfirmed)
                    <input form="two-factor-confirm-form" type="submit" value="{{ __('Confirm') }}" class="btn btn-neutral">
                @else
                    <form method="POST" action="/user/two-factor-recovery-codes">
                        @csrf

                        <input type="submit" value="{{ __('Regenerate Recovery Codes') }}" class="btn btn-neutral">
                    </form>
                @endif

                <form method="POST" action="{{ route('two-factor.disable') }}">
                    @method('DELETE')
                    @csrf

                    <input
                        type="submit"
                        value="{{ __($twoFactorConfirmed ? 'Disable' : 'Cancel') }}"
                        @class([
                            'btn',
                            'btn-error' => $twoFactorConfirmed,
                            'btn-outline btn-neutral' => !$twoFactorConfirmed,
                        ])
                    >
                </form>
            @endif
        </div>
    </x-slot>
</x-form-section>
