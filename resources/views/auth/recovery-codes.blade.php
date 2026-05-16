<x-guest-layout>
    <div class="mb-4">
        <h2 class="text-xl font-semibold text-gray-800">{{ __('Recovery Codes') }}</h2>
        <p class="mt-2 text-sm text-gray-600">
            {{ __('Store these recovery codes in a secure password manager. They can be used to recover access to your account if your two-factor authentication device is lost.') }}
        </p>
    </div>

    <div class="bg-gray-100 rounded-lg p-4 mb-4">
        <div class="grid grid-cols-2 gap-2">
            @foreach($recoveryCodes as $code)
                <div class="font-mono text-sm bg-white p-2 rounded border border-gray-300 text-center">
                    {{ $code }}
                </div>
            @endforeach
        </div>
    </div>

    <div class="flex items-center justify-between">
        <a href="{{ route('profile.edit') }}" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
            {{ __('Back to Profile') }}
        </a>

        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
            {{ __('Print Codes') }}
        </button>
    </div>

    <div class="mt-4 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
        <p class="text-xs text-gray-600">
            <strong>{{ __('Important:') }}</strong>
            {{ __('Each recovery code can only be used once. After using a code, it will be invalidated.') }}
        </p>
    </div>
</x-guest-layout>
