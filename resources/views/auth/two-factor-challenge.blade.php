<x-guest-layout>
    <div class="mb-4 text-sm text-gray-600">
        {{ __('Please enter the 6-digit code sent to your email address to complete your login.') }}
    </div>

    @if(!session('2fa:email_sent', true))
        <div class="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-md">
            <p class="text-sm font-medium text-yellow-800">
                {{ __('Warning: Email could not be sent due to mail server configuration. The code is displayed below for testing purposes.') }}
            </p>
        </div>
    @endif

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    @if($errors->any())
        <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-md">
            <p class="text-sm font-medium text-red-800">
                @foreach($errors->all() as $error)
                    {{ $error }}<br>
                @endforeach
            </p>
        </div>
    @endif

    <form method="POST" action="{{ route('two-factor.verify') }}">
        @csrf

        <!-- 2FA Code -->
        <div>
            <x-input-label for="code" :value="__('Authentication Code')" />
            <x-text-input id="code" class="block mt-1 w-full text-center text-2xl tracking-widest" 
                type="text" 
                name="code" 
                required 
                autofocus 
                maxlength="10"
                placeholder="Enter code" />
            <x-input-error :messages="$errors->get('code')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between mt-6">
            <button type="button" onclick="document.getElementById('resend-form').submit()" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                {{ __('Resend Code') }}
            </button>

            <x-primary-button>
                {{ __('Verify') }}
            </x-primary-button>
        </div>
    </form>

    <form id="resend-form" method="POST" action="{{ route('two-factor.resend') }}" class="hidden">
        @csrf
    </form>

    <div class="mt-4 text-center">
        <a href="{{ route('login') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Back to Login') }}
        </a>
    </div>

    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
        <p class="text-xs text-gray-600">
            <strong>{{ __('Lost access to your email?') }}</strong><br>
            {{ __('You can use one of your recovery codes instead of the authentication code.') }}
        </p>
    </div>
</x-guest-layout>
