<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-extrabold text-lg text-slate-900 leading-tight">
                Two-Factor Authentication (2FA) Setup
            </h2>
            <p class="text-xs text-slate-500">Secure your forensic account with Time-based One-Time Passcodes (TOTP).</p>
        </div>
    </x-slot>

    <div class="py-6 max-w-4xl mx-auto space-y-6">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Profile Settings', 'url' => route('profile.edit')],
            ['label' => '2FA Setup']
        ]" />

        <!-- Status Notifications -->
        @if (session('status') === 'two-factor-authentication-enabled')
            <div class="p-4 bg-emerald-50 border border-emerald-300 text-emerald-900 rounded-lg text-sm font-medium">
                ✓ Two-Factor Authentication has been successfully enabled on your account!
            </div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 border border-red-300 text-red-900 rounded-lg text-sm font-medium">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 space-y-6">
            <div class="flex items-start justify-between border-b border-slate-200 pb-4">
                <div>
                    <h3 class="font-bold text-slate-900 text-base">Step 1: Scan QR Code with Authenticator App</h3>
                    <p class="text-xs text-slate-500 mt-1 max-w-xl">
                        Open Google Authenticator, Microsoft Authenticator, or 1Password on your mobile device and scan the QR code below.
                    </p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider {{ $isConfirmed ? 'bg-emerald-100 text-emerald-800 border border-emerald-300' : 'bg-amber-100 text-amber-800 border border-amber-300' }}">
                    {{ $isConfirmed ? 'Status: Active & Enabled' : 'Status: Pending Confirmation' }}
                </span>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 items-center">
                <!-- QR Code Display -->
                <div class="flex flex-col items-center justify-center p-6 bg-slate-50 border border-slate-200 rounded-xl">
                    <img src="{{ $qrCodeUrl }}" alt="2FA QR Code" class="w-48 h-48 rounded-lg shadow-xs border border-white" />
                    <p class="text-[11px] font-semibold text-slate-500 mt-3 uppercase tracking-wider">Scan with Authenticator App</p>
                </div>

                <!-- Manual Secret Key & Instructions -->
                <div class="space-y-4 text-xs">
                    <div>
                        <span class="block font-bold text-slate-700 uppercase mb-1">Manual Secret Key:</span>
                        <div class="p-3 bg-slate-900 text-emerald-400 font-mono text-base font-bold rounded-lg border border-slate-800 select-all tracking-wider text-center">
                            {{ chunk_split($secretKey, 4, ' ') }}
                        </div>
                        <p class="text-[11px] text-slate-500 mt-1">If you cannot scan QR codes, enter this secret key manually into your app.</p>
                    </div>

                    <!-- Step 2: Confirmation Form -->
                    @if(!$isConfirmed)
                        <form method="POST" action="{{ route('two-factor.enable') }}" class="pt-3 border-t border-slate-200 space-y-3">
                            @csrf
                            <label for="code" class="block font-bold text-slate-900 uppercase">Step 2: Enter 6-Digit Passcode to Activate</label>
                            <div class="flex gap-2">
                                <input type="text" name="code" id="code" required maxlength="6" placeholder="000000" class="flex-1 rounded-md border-slate-300 font-mono text-center font-bold text-lg tracking-widest focus:border-blue-500 focus:ring-blue-500">
                                <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white font-bold text-xs uppercase tracking-wider rounded-md hover:bg-blue-700 transition">
                                    Confirm & Enable
                                </button>
                            </div>
                        </form>
                    @else
                        <!-- Disable 2FA Action -->
                        <form method="POST" action="{{ route('two-factor.disable') }}" class="pt-3 border-t border-slate-200" onsubmit="return confirm('Are you sure you want to disable 2FA protection on your forensic account?');">
                            @csrf
                            <button type="submit" class="px-4 py-2 bg-red-600 text-white font-bold text-xs uppercase tracking-wider rounded-md hover:bg-red-700 transition">
                                Disable Two-Factor Protection
                            </button>
                        </form>
                    @endif
                </div>
            </div>

            <!-- Step 3: Emergency Recovery Codes -->
            <div class="pt-6 border-t border-slate-200 space-y-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-slate-900 text-sm">Emergency Recovery Keys</h4>
                        <p class="text-xs text-slate-500 mt-0.5">Save these 8 emergency keys in a secure location. Each code can be used once if you lose your phone.</p>
                    </div>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 bg-slate-900 p-4 rounded-xl font-mono text-xs text-slate-200 text-center">
                    @foreach($recoveryCodes as $rCode)
                        <div class="p-2 bg-slate-800/80 rounded border border-slate-700 font-semibold text-emerald-400 select-all">
                            {{ $rCode }}
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
