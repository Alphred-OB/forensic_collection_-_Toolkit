<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto space-y-6">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Cases', 'url' => route('cases.index')],
            ['label' => 'Create New Case']
        ]" />
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                Create New Investigation Case
            </h2>
            <p class="text-xs text-slate-500 mt-1">Initiate a formal forensic investigation folder and assign security personnel.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <form method="POST" action="{{ route('cases.store') }}" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Case Number -->
                    <div>
                        <label for="case_number" class="block text-sm font-bold text-slate-800 mb-1">Case ID / Reference Number</label>
                        <input type="text" name="case_number" id="case_number" value="CASE-{{ date('Ymd') }}-{{ rand(1000, 9999) }}" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                        @error('case_number') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Priority Level -->
                    <div>
                        <label for="priority" class="block text-sm font-bold text-slate-800 mb-1">Priority Level</label>
                        <select name="priority" id="priority" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Normal" selected>Normal Priority</option>
                            <option value="High">High Priority</option>
                            <option value="Critical">Critical Priority</option>
                            <option value="Low">Low Priority</option>
                        </select>
                        @error('priority') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-bold text-slate-800 mb-1">Case Title</label>
                    <input type="text" name="title" id="title" required placeholder="e.g. Corporate Data Breach Incident" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('title') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Tags -->
                <div>
                    <label for="tags" class="block text-sm font-bold text-slate-800 mb-1">Classification Tags (Comma Separated)</label>
                    <input type="text" name="tags" id="tags" placeholder="e.g. Cyber Crime, Financial Fraud, Mobile Forensics" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-[11px] text-slate-500 mt-1">Separate multiple tags with commas.</p>
                    @error('tags') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-bold text-slate-800 mb-1">Detailed Case Description</label>
                    <textarea name="description" id="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Provide background context..."></textarea>
                    @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Assign Personnel -->
                <div>
                    <label class="block text-sm font-bold text-slate-800 mb-2">Assign Investigators / Personnel</label>
                    <div class="space-y-2 border border-slate-200 p-4 rounded-md max-h-48 overflow-y-auto bg-slate-50">
                        @foreach($users as $user)
                            @if($user->id !== Auth::id())
                                <div class="flex items-center">
                                    <input type="checkbox" name="assigned_users[]" value="{{ $user->id }}" id="user_{{ $user->id }}" class="rounded border-slate-300 text-blue-600 shadow-sm focus:ring-blue-500 h-4 w-4">
                                    <label for="user_{{ $user->id }}" class="ms-2 text-xs text-slate-800 font-medium">{{ $user->name }} ({{ $user->role }}) &mdash; {{ $user->email }}</label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-200">
                    <a href="{{ route('cases.index') }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition shadow-sm">Create Case</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
