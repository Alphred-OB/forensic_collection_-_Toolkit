<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto space-y-6">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Cases', 'url' => route('cases.index')],
            ['label' => $case->case_number, 'url' => route('cases.show', $case->id)],
            ['label' => 'Edit Case Details']
        ]" />
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex justify-between items-center">
            <div>
                <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                    Edit Case: {{ $case->case_number }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Update title, description, priority level, and classification tags.</p>
            </div>
            <a href="{{ route('cases.show', $case->id) }}" class="px-3.5 py-2 bg-slate-200 text-slate-700 text-xs font-bold rounded-md hover:bg-slate-300 transition">Back to Case</a>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <form method="POST" action="{{ route('cases.update', $case->id) }}" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Case Number (Read-Only) -->
                    <div>
                        <label class="block text-sm font-bold text-slate-800 mb-1">Case ID / Reference Number (Immutable)</label>
                        <input type="text" value="{{ $case->case_number }}" disabled class="w-full rounded-md border-slate-200 bg-slate-100 text-slate-500 text-sm font-mono cursor-not-allowed">
                    </div>

                    <!-- Priority Level -->
                    <div>
                        <label for="priority" class="block text-sm font-bold text-slate-800 mb-1">Priority Level</label>
                        <select name="priority" id="priority" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="Normal" {{ $case->priority === 'Normal' ? 'selected' : '' }}>Normal Priority</option>
                            <option value="High" {{ $case->priority === 'High' ? 'selected' : '' }}>High Priority</option>
                            <option value="Critical" {{ $case->priority === 'Critical' ? 'selected' : '' }}>Critical Priority</option>
                            <option value="Low" {{ $case->priority === 'Low' ? 'selected' : '' }}>Low Priority</option>
                        </select>
                        @error('priority') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Title -->
                <div>
                    <label for="title" class="block text-sm font-bold text-slate-800 mb-1">Case Title</label>
                    <input type="text" name="title" id="title" value="{{ old('title', $case->title) }}" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('title') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Classification Tags -->
                <div>
                    <label for="tags" class="block text-sm font-bold text-slate-800 mb-1">Classification Tags (Comma Separated)</label>
                    <input type="text" name="tags" id="tags" value="{{ old('tags', $case->tags) }}" placeholder="e.g. Cyber Crime, Financial Fraud, Mobile Forensics" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <p class="text-[11px] text-slate-500 mt-1">Separate multiple tags with commas.</p>
                    @error('tags') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-bold text-slate-800 mb-1">Detailed Case Description</label>
                    <textarea name="description" id="description" rows="4" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">{{ old('description', $case->description) }}</textarea>
                    @error('description') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-200">
                    <a href="{{ route('cases.show', $case->id) }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
