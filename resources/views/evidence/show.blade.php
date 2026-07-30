<x-app-layout>
    <div class="py-6 space-y-6">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Cases', 'url' => route('cases.index')],
            ['label' => $evidence->case->case_number, 'url' => route('cases.show', $evidence->case->id)],
            ['label' => 'Evidence: ' . $evidence->evidence_number]
        ]" />
        <!-- Evidence Title Banner & Action Controls (Inside Page Content) -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <h2 class="font-extrabold text-xl md:text-2xl text-slate-900 leading-tight">
                    Evidence: {{ $evidence->evidence_number }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Case: <a href="{{ route('cases.show', $evidence->case->id) }}" class="font-bold text-blue-600 hover:underline">{{ $evidence->case->case_number }}</a></p>
            </div>
            <div class="flex items-center flex-wrap gap-2 shrink-0">
                <a href="{{ route('reports.coc', $evidence->id) }}" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 shadow-xs transition">
                    Export CoC Form (PDF/HTML)
                </a>
                @if($evidence->current_custodian_id === Auth::id() || Auth::user()->role === 'Administrator')
                    <a href="{{ route('custody.create', $evidence->id) }}" class="inline-flex items-center px-3.5 py-2 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-xs transition">
                        Transfer Custody
                    </a>
                @endif
                <a href="{{ route('evidence.download', $evidence->id) }}" class="inline-flex items-center px-3.5 py-2 bg-slate-900 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-xs transition">
                    Secure Download
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 border border-red-300 text-red-800 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('error') }}
            </div>
        @endif

            <!-- Integrity Health Banner -->
            <div class="p-4 rounded-lg border flex items-center justify-between {{ $isIntegrityValid ? 'bg-emerald-50 border-emerald-300 text-emerald-800' : 'bg-red-50 border-red-300 text-red-800' }}">
                <div>
                    <h4 class="font-bold text-sm uppercase tracking-wide">
                        {{ $isIntegrityValid ? 'Cryptographic Integrity Verified' : 'CRITICAL WARNING: Tampering Detected!' }}
                    </h4>
                    <p class="text-xs mt-0.5">
                        {{ $isIntegrityValid ? 'The file stored on disk matches the SHA-256 hash generated at intake.' : 'The stored evidence file hash does NOT match the intake SHA-256 record! Evidence may be altered.' }}
                    </p>
                </div>
                <span class="px-3 py-1 text-xs font-mono font-bold rounded-full {{ $isIntegrityValid ? 'bg-emerald-200 text-emerald-900' : 'bg-red-200 text-red-900' }}">
                    {{ $isIntegrityValid ? 'MATCH_OK' : 'HASH_MISMATCH' }}
                </span>
            </div>

            <!-- Evidence Metadata Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Metadata Card -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Evidence Specifications</h3>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Source Device:</dt><dd class="font-semibold text-gray-800">{{ $evidence->source_device }}</dd></div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500">Classification:</dt>
                            <dd class="font-semibold text-indigo-600 uppercase">
                                {{ $evidence->classification === 'custom' ? ($evidence->custom_classification ?: 'Custom Classification') : str_replace('_', ' ', $evidence->classification) }}
                            </dd>
                        </div>
                        @if($evidence->parent)
                            <div class="flex justify-between bg-blue-50 p-2 rounded border border-blue-100"><dt class="text-blue-700 font-bold">Parent Drive / Container:</dt><dd class="font-bold text-blue-900"><a href="{{ route('evidence.show', $evidence->parent->id) }}" class="underline">{{ $evidence->parent->evidence_number }} &mdash; {{ $evidence->parent->file_name }}</a></dd></div>
                        @endif
                        @if($evidence->subItems->isNotEmpty())
                            <div class="pt-2 border-t border-slate-100">
                                <dt class="text-xs font-bold text-slate-700 uppercase mb-1">Sub-Items / Extracted Partitions ({{ $evidence->subItems->count() }}):</dt>
                                <dd class="space-y-1">
                                    @foreach($evidence->subItems as $sub)
                                        <div class="flex justify-between text-xs p-2 bg-slate-50 rounded border border-slate-200">
                                            <a href="{{ route('evidence.show', $sub->id) }}" class="font-bold text-blue-600 hover:underline">{{ $sub->evidence_number }} &mdash; {{ $sub->file_name }}</a>
                                            <span class="font-mono text-slate-500 text-[10px]">{{ str_replace('_', ' ', $sub->classification) }}</span>
                                        </div>
                                    @endforeach
                                </dd>
                            </div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500">Original File Name:</dt><dd class="font-mono text-gray-800">{{ $evidence->file_name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">File Size:</dt><dd class="font-mono text-gray-800">{{ number_format($evidence->file_size / 1024, 2) }} KB ({{ number_format($evidence->file_size) }} bytes)</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">MIME Content Type:</dt><dd class="font-mono text-blue-700 bg-blue-50 px-2 py-0.5 rounded text-xs font-semibold">{{ $fileMetadata['mime_type'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">File Extension Format:</dt><dd class="font-mono text-slate-800 font-bold uppercase">{{ $fileMetadata['extension'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Disk Storage Timestamp:</dt><dd class="font-mono text-slate-700 text-xs">{{ $fileMetadata['last_modified'] }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Collection Location:</dt><dd class="text-gray-800">{{ $evidence->collected_location }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Collected At:</dt><dd class="text-gray-800">{{ $evidence->collected_at->format('Y-m-d H:i') }}</dd></div>
                    </dl>
                </div>

                <!-- Hash & Custody Card -->
                <div class="bg-white p-6 rounded-lg shadow-sm">
                    <h3 class="text-lg font-bold text-gray-800 border-b pb-2 mb-4">Cryptographic & Custody Status</h3>
                    <div class="mb-4">
                        <label class="block text-xs font-semibold text-gray-500 uppercase">SHA-256 Fingerprint</label>
                        <div class="mt-1 p-2 bg-gray-900 text-green-400 font-mono text-xs rounded break-all select-all">
                            {{ $evidence->file_hash_sha256 }}
                        </div>
                    </div>
                    <div class="space-y-3 text-sm">
                        <div class="flex justify-between"><dt class="text-gray-500">Uploaded By:</dt><dd class="font-semibold text-gray-800">{{ $evidence->uploader->name }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Current Custodian:</dt><dd class="font-bold text-indigo-700 bg-indigo-50 px-2 py-0.5 rounded">{{ $evidence->currentCustodian->name }}</dd></div>
                    </div>
                </div>
            </div>

            <!-- Chain of Custody History & Transfers -->
            <div class="bg-white p-6 rounded-lg shadow-sm">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Chain of Custody (CoC) Audit Log</h3>
                
                @if($evidence->transfers->isEmpty())
                    <p class="text-sm text-gray-500">No custody transfers recorded yet. The evidence is currently with original uploader <span class="font-semibold text-gray-700">{{ $evidence->uploader->name }}</span>.</p>
                @else
                    <div class="relative border-l-2 border-indigo-200 ml-4 space-y-6">
                        @foreach($evidence->transfers as $transfer)
                            <div class="ml-6">
                                <span class="absolute -left-2.5 flex items-center justify-center w-5 h-5 rounded-full {{ $transfer->status === 'accepted' ? 'bg-indigo-600 ring-4 ring-white text-white text-xs' : 'bg-amber-400' }}"></span>
                                <div class="p-4 bg-gray-50 rounded-md border border-gray-200">
                                    <div class="flex justify-between items-center mb-1">
                                        <span class="font-bold text-sm text-gray-800">{{ $transfer->fromUser->name }} &rarr; {{ $transfer->toUser->name }}</span>
                                        <span class="text-xs font-semibold px-2 py-0.5 rounded uppercase {{ $transfer->status === 'accepted' ? 'bg-green-100 text-green-800' : ($transfer->status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">
                                            {{ $transfer->status }}
                                        </span>
                                    </div>
                                    <p class="text-xs text-gray-600 mb-2"><strong>Reason:</strong> {{ $transfer->reason }}</p>
                                    <p class="text-xs text-gray-400">Initiated: {{ $transfer->transferred_at->format('Y-m-d H:i') }} @if($transfer->accepted_at)| Accepted: {{ $transfer->accepted_at->format('Y-m-d H:i') }}@endif</p>
                                    
                                    @if($transfer->status === 'pending' && $transfer->to_user_id === Auth::id())
                                        <div class="mt-3 flex space-x-2">
                                            <form method="POST" action="{{ route('custody.accept', $transfer->id) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-green-600 text-white text-xs font-bold rounded hover:bg-green-700">Accept Custody</button>
                                            </form>
                                            <form method="POST" action="{{ route('custody.reject', $transfer->id) }}">
                                                @csrf
                                                <button type="submit" class="px-3 py-1 bg-red-600 text-white text-xs font-bold rounded hover:bg-red-700">Reject</button>
                                            </form>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
