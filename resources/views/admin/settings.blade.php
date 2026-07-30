<x-app-layout>
    <div class="py-6 space-y-6">
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                System Security & Vault Policy Settings
            </h2>
            <p class="text-xs text-slate-500 mt-1">Configure global vault storage limits, evidence MIME type restrictions, and user session policies.</p>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6 max-w-3xl">
            <form method="POST" action="{{ route('admin.settings.update') }}" class="space-y-6">
                @csrf

                <!-- File Upload Size Limit -->
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Max Evidence Upload Size (MB)</label>
                    <p class="text-xs text-slate-500 mb-2">Maximum single physical disk image or evidence file payload size accepted by intake API.</p>
                    <input type="number" name="max_upload_size_mb" value="{{ old('max_upload_size_mb', $settings['max_upload_size_mb']) }}" required class="w-full md:w-64 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('max_upload_size_mb')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Allowed Extensions -->
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Allowed File Extensions</label>
                    <p class="text-xs text-slate-500 mb-2">Comma-separated list of permitted digital forensic file extensions.</p>
                    <input type="text" name="allowed_extensions" value="{{ old('allowed_extensions', $settings['allowed_extensions']) }}" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                    @error('allowed_extensions')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Session Timeout -->
                <div>
                    <label class="block text-sm font-bold text-slate-900 mb-1">Session Inactivity Timeout (Minutes)</label>
                    <p class="text-xs text-slate-500 mb-2">Automatic logout period for idle investigator/admin sessions.</p>
                    <input type="number" name="session_timeout_minutes" value="{{ old('session_timeout_minutes', $settings['session_timeout_minutes']) }}" required class="w-full md:w-64 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('session_timeout_minutes')
                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Mandatory 2FA Policy -->
                <div class="pt-2 border-t border-slate-200">
                    <div class="flex items-center">
                        <input type="checkbox" name="mandatory_2fa" id="mandatory_2fa" value="1" {{ old('mandatory_2fa', $settings['mandatory_2fa']) === '1' ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500 h-4 w-4">
                        <label for="mandatory_2fa" class="ms-2 text-sm font-bold text-slate-900">Enforce Mandatory Two-Factor Authentication (2FA)</label>
                    </div>
                    <p class="text-xs text-slate-500 ms-6 mt-0.5">Require all Investigators and Administrators to setup 2FA key before accessing vault.</p>
                </div>

                <div class="pt-4 flex justify-end">
                    <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-sm transition">
                        Save Policy Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
