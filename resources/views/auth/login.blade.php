<x-guest-layout :title="__('Login')">
    <x-auth-card>
        <x-session-message :message="session('session-message')" class="mb-3"></x-session-message>

        <form method="POST" action="{{ route('login') }}" class="w-full">
            @csrf

            <div class="form-control w-full">
                <label class="label" for="email">
                    <span class="label-text">{{ __('Email') }}</span>
                </label>
                <input id="email" type="email" name="email" class="input input-md w-full{{ $errors->get('email') ? ' input-error' : '' }}" value="{{ old('email') }}" required autofocus autocomplete="username" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('email')" />
                    </span>
                </label>
            </div>

            <div class="form-control w-full">
                <label class="label" for="password">
                    <span class="label-text">{{ __('Password') }}</span>
                </label>
                <input id="password" type="password" name="password" class="input input-md w-full{{ $errors->get('password') ? ' input-error' : '' }}" required autocomplete="current-password" />
                <label class="label">
                    <span class="label-text-alt">
                        <x-input-error :messages="$errors->get('password')" />
                    </span>
                </label>
            </div>

            <div class="form-control w-fit">
                <label class="label cursor-pointer gap-4">
                    <input type="checkbox" {{ old('remember') ? 'checked' : '' }} class="checkbox checkbox-secondary" name="remember" />
                    <span class="label-text">{{ __('Remember me') }}</span> 
                </label>
            </div>

            <div class="card-actions justify-end items-center gap-6 mt-6">
                @if (Route::has('password.request'))
                    <a class="link" href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
                @endif

                <input type="submit" value="{{ __('Log in') }}" class="btn btn-secondary" />
            </div>
        </form>
    </x-auth-card>
</x-guest-layout>