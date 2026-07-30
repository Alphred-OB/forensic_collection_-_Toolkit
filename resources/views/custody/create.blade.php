<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Initiate Custody Transfer: {{ $evidence->evidence_number }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('custody.store', $evidence->id) }}">
                        @csrf

                        <!-- Target User -->
                        <div class="mb-4">
                            <label for="to_user_id" class="block text-sm font-medium text-gray-700">Recipient Personnel</label>
                            <select name="to_user_id" id="to_user_id" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">-- Select Recipient --</option>
                                @foreach($users as $user)
                                    <option value="{{ $user->id }}">{{ $user->name }} ({{ $user->role }}) - {{ $user->email }}</option>
                                @endforeach
                            </select>
                            @error('to_user_id') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <!-- Reason -->
                        <div class="mb-6">
                            <label for="reason" class="block text-sm font-medium text-gray-700">Official Transfer Reason & Purpose</label>
                            <textarea name="reason" id="reason" rows="4" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="e.g. Handover for forensic laboratory analysis / Storage in physical vault..."></textarea>
                            @error('reason') <p class="text-red-500 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="flex justify-end space-x-3">
                            <a href="{{ route('evidence.show', $evidence->id) }}" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">Cancel</a>
                            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-semibold">Submit Transfer Request</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
