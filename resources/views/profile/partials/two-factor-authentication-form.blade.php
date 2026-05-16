<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            {{ __('Two-Factor Authentication') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __('Add additional security to your account using two-factor authentication.') }}
        </p>
    </header>

    @if(session('status') === '2fa-enabled')
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm font-medium text-green-800">
                {{ __('Two-factor authentication has been enabled!') }}
            </p>
        </div>

        @if(session('recovery_codes'))
            <div class="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
                <p class="text-sm font-medium text-yellow-800 mb-2">
                    {{ __('Store these recovery codes in a secure location:') }}
                </p>
                <div class="grid grid-cols-2 gap-2 mt-2">
                    @foreach(session('recovery_codes') as $code)
                        <div class="font-mono text-xs bg-white p-2 rounded border border-gray-300 text-center">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>
                <p class="text-xs text-gray-600 mt-2">
                    {{ __('You can view these codes again from your profile.') }}
                </p>
            </div>
        @endif
    @endif

    @if(session('status') === '2fa-disabled')
        <div class="mt-4 p-4 bg-gray-50 border border-gray-200 rounded-md">
            <p class="text-sm font-medium text-gray-800">
                {{ __('Two-factor authentication has been disabled.') }}
            </p>
        </div>
    @endif

    @if(session('status') === '2fa-recovery-regenerated')
        <div class="mt-4 p-4 bg-green-50 border border-green-200 rounded-md">
            <p class="text-sm font-medium text-green-800 mb-2">
                {{ __('New recovery codes have been generated!') }}
            </p>
            @if(session('recovery_codes'))
                <div class="grid grid-cols-2 gap-2 mt-2">
                    @foreach(session('recovery_codes') as $code)
                        <div class="font-mono text-xs bg-white p-2 rounded border border-gray-300 text-center">
                            {{ $code }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    @endif

    <div class="mt-6 space-y-6">
        @if(!$user->two_factor_enabled)
            <!-- Enable 2FA -->
            <div>
                <p class="text-sm text-gray-600 mb-4">
                    {{ __('When two-factor authentication is enabled, you will be prompted for a secure code sent to your email during login.') }}
                </p>

                <form method="POST" action="{{ route('two-factor.enable') }}">
                    @csrf

                    <div class="flex items-center gap-4">
                        <x-primary-button>
                            {{ __('Enable Two-Factor Authentication') }}
                        </x-primary-button>
                    </div>
                </form>
            </div>
        @else
            <!-- 2FA is Enabled -->
            <div>
                <div class="p-4 bg-green-50 border border-green-200 rounded-md mb-4">
                    <p class="text-sm font-medium text-green-800">
                        ✓ {{ __('Two-factor authentication is currently enabled.') }}
                    </p>
                </div>

                <div class="space-y-4">
                    <!-- View Recovery Codes -->
                    <div>
                        <a href="{{ route('two-factor.recovery-codes') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                            {{ __('View Recovery Codes') }}
                        </a>
                    </div>

                    <!-- Regenerate Recovery Codes -->
                    <div>
                        <form method="POST" action="{{ route('two-factor.recovery-codes.regenerate') }}" onsubmit="return confirm('Are you sure you want to regenerate recovery codes? Your old codes will no longer work.');">
                            @csrf

                            <div>
                                <x-input-label for="password_regen" :value="__('Password (required to regenerate codes)')" />
                                <x-text-input id="password_regen" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                                    {{ __('Regenerate Recovery Codes') }}
                                </button>
                            </div>
                        </form>
                    </div>

                    <!-- Disable 2FA -->
                    <div class="pt-4 border-t border-gray-200">
                        <form method="POST" action="{{ route('two-factor.disable') }}" onsubmit="return confirm('Are you sure you want to disable two-factor authentication?');">
                            @csrf

                            <div>
                                <x-input-label for="password_disable" :value="__('Password (required to disable 2FA)')" />
                                <x-text-input id="password_disable" name="password" type="password" class="mt-1 block w-full" required />
                                <x-input-error :messages="$errors->get('password')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 active:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                                    {{ __('Disable Two-Factor Authentication') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    </div>
</section>
