<x-guest-layout>
    <div class="mb-6 text-center">
        <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-blue-100 text-blue-600 mb-3">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
        </div>
        <h2 class="text-xl font-extrabold text-slate-900">Two-Factor Secondary Verification</h2>
        <p class="text-xs text-slate-500 mt-1">Please confirm access to your account by entering the 6-digit passcode from your authenticator app.</p>
    </div>

    <!-- Session Error Message -->
    @if ($errors->any())
        <div class="mb-4 bg-red-50 border border-red-300 text-red-800 p-3 rounded-lg text-xs font-medium">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div x-data="{ recovery: false }">
        <!-- 6-digit TOTP Code Form -->
        <form method="POST" action="{{ route('two-factor.verify') }}" x-show="!recovery">
            @csrf
            <div>
                <x-input-label for="code" :value="__('6-Digit Authenticator Passcode')" class="text-xs uppercase font-bold" />
                <x-text-input id="code" class="block mt-1 w-full text-center tracking-[0.5em] font-mono text-xl py-2.5 font-bold" type="text" name="code" autofocus autocomplete="one-time-code" maxlength="6" placeholder="000000" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button type="button" @click="recovery = true" class="text-xs text-slate-500 hover:text-blue-600 font-semibold underline">
                    Use Emergency Recovery Code
                </button>

                <x-primary-button class="ms-3">
                    {{ __('Verify Access') }}
                </x-primary-button>
            </div>
        </form>

        <!-- Emergency Recovery Code Form -->
        <form method="POST" action="{{ route('two-factor.verify') }}" x-show="recovery" style="display: none;">
            @csrf
            <div>
                <x-input-label for="recovery_code" :value="__('8-Digit Emergency Recovery Key')" class="text-xs uppercase font-bold text-amber-700" />
                <x-text-input id="recovery_code" class="block mt-1 w-full font-mono text-sm uppercase text-center tracking-widest py-2.5" type="text" name="recovery_code" placeholder="XXXX-XXXX" />
            </div>

            <div class="mt-4 flex items-center justify-between">
                <button type="button" @click="recovery = false" class="text-xs text-slate-500 hover:text-blue-600 font-semibold underline">
                    Use Authenticator App Instead
                </button>

                <x-primary-button class="ms-3 bg-amber-600 hover:bg-amber-700">
                    {{ __('Verify Recovery Key') }}
                </x-primary-button>
            </div>
        </form>
    </div>
</x-guest-layout>
