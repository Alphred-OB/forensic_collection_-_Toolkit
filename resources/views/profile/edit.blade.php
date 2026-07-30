<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    <section class="space-y-4">
                        <header>
                            <h2 class="text-lg font-bold text-slate-900">
                                {{ __('Two-Factor Authentication (2FA)') }}
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ __('Add additional security to your forensic account using Time-based One-Time Passcodes (TOTP).') }}
                            </p>
                        </header>

                        <div class="flex items-center justify-between p-4 bg-slate-50 border border-slate-200 rounded-lg">
                            <div>
                                <span class="text-xs font-bold uppercase tracking-wider block text-slate-700">2FA Status:</span>
                                <span class="text-xs font-semibold {{ Auth::user()->two_factor_confirmed_at ? 'text-emerald-600' : 'text-amber-600' }}">
                                    {{ Auth::user()->two_factor_confirmed_at ? '✓ Active & Enabled' : '⚠ Disabled / Pending Confirmation' }}
                                </span>
                            </div>
                            <a href="{{ route('two-factor.setup') }}" class="px-4 py-2 bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-md hover:bg-slate-800 transition">
                                {{ Auth::user()->two_factor_confirmed_at ? 'Manage 2FA & Keys' : 'Enable 2FA Protection' }}
                            </a>
                        </div>
                    </section>
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
