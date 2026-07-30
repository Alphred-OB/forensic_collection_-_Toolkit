<x-app-layout>
    <div class="py-6 max-w-4xl mx-auto space-y-6">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Cases', 'url' => route('cases.index')],
            ['label' => $case->case_number, 'url' => route('cases.show', $case->id)],
            ['label' => 'Upload Evidence Item']
        ]" />
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                Upload Evidence Item &mdash; Case: {{ $case->case_number }}
            </h2>
            <p class="text-xs text-slate-500 mt-1">Ingest digital evidence payload, assign parent drive relationships, and compute SHA-256 fingerprint.</p>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-6">
            <form method="POST" action="{{ route('evidence.store', $case->id) }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <!-- Evidence ID -->
                    <div>
                        <label for="evidence_number" class="block text-sm font-bold text-slate-800 mb-1">Evidence Tag / Identifier</label>
                        <input type="text" name="evidence_number" id="evidence_number" value="EVD-{{ date('Ymd') }}-{{ rand(100, 999) }}" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500 font-mono">
                        @error('evidence_number') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <!-- Optional Parent Item (Sub-item / Partition linking) -->
                    <div>
                        <label for="parent_id" class="block text-sm font-bold text-slate-800 mb-1">Parent Evidence Item (Optional)</label>
                        <select name="parent_id" id="parent_id" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">None (Top-Level Primary Evidence)</option>
                            @foreach($parentItems as $p)
                                <option value="{{ $p->id }}">{{ $p->evidence_number }} &mdash; {{ $p->file_name }} ({{ str_replace('_', ' ', $p->classification) }})</option>
                            @endforeach
                        </select>
                        <p class="text-[11px] text-slate-500 mt-1">Link as a sub-partition or extracted file payload to a physical drive.</p>
                    </div>
                </div>

                <!-- Source Device -->
                <div>
                    <label for="source_device" class="block text-sm font-bold text-slate-800 mb-1">Source Device / Hardware Model</label>
                    <input type="text" name="source_device" id="source_device" required placeholder="e.g. Samsung Galaxy S21 (IMEI: 3582...)" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('source_device') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <!-- Classification with Interactive Tooltip & Descriptions -->
                <div x-data="{ 
                    selected: 'original',
                    descriptions: {
                        'original': 'Physical hardware device or unmodified primary acquisition media collected at scene.',
                        'forensic_copy': 'Bit-stream image (.E01, .raw, .dd) created via write-blocker hardware.',
                        'export': 'Tool extraction payload, database dump, or specific filesystem partition export.',
                        'screenshot': 'Screen captures, scene photography, or visual interface snapshots.',
                        'reconstructed': 'Carved, reconstructed, or reassembled file payload.',
                        'custom': 'User-defined forensic evidence classification type.'
                    }
                }">
                    <div class="flex items-center justify-between mb-1">
                        <label for="classification" class="block text-sm font-bold text-slate-800">
                            Evidence Classification
                        </label>
                        <!-- Tooltip Icon -->
                        <div class="relative group cursor-pointer">
                            <span class="inline-flex items-center justify-center w-4 h-4 text-[10px] font-bold text-slate-600 bg-slate-200 rounded-full group-hover:bg-blue-600 group-hover:text-white transition">?</span>
                            <div class="absolute right-0 bottom-full mb-2 hidden group-hover:block w-72 p-2.5 bg-slate-900 text-slate-200 text-xs rounded-md shadow-xl border border-slate-700 z-50 leading-normal">
                                <p class="font-bold text-amber-400 mb-1">Classification Guidelines:</p>
                                <ul class="space-y-1 text-[11px] list-disc list-inside text-slate-300">
                                    <li><strong>Original:</strong> Unaltered source drive/device.</li>
                                    <li><strong>Forensic Copy:</strong> Bit-for-bit duplicate image.</li>
                                    <li><strong>Export:</strong> Logical extract or log file dump.</li>
                                    <li><strong>Custom:</strong> Custom defined classification.</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <select name="classification" id="classification" x-model="selected" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        <option value="original">Original Physical Device / Raw Copy &mdash; (Unaltered hardware/media)</option>
                        <option value="forensic_copy">Forensic Bit-Stream Image (.E01 / .raw) &mdash; (Bit-for-bit forensic image)</option>
                        <option value="export">Tool Export / Log Extract / Partition &mdash; (Logical extract or log dump)</option>
                        <option value="screenshot">Screen Capture / Photos &mdash; (Visual documentation)</option>
                        <option value="reconstructed">Reconstructed File &mdash; (Carved file payload)</option>
                        <option value="custom">+ Add Custom Classification Category...</option>
                    </select>

                    <!-- Live Dynamic Description Hint -->
                    <p class="text-xs text-slate-600 mt-1.5 bg-slate-50 p-2 rounded border border-slate-200" x-text="descriptions[selected]"></p>

                    <!-- Dynamic Custom Classification Input Field -->
                    <div x-show="selected === 'custom'" class="mt-3 p-3 bg-blue-50/60 border border-blue-200 rounded-md space-y-1" style="display: none;">
                        <label for="custom_classification" class="block text-xs font-bold text-blue-900">Custom Classification Label Name</label>
                        <input type="text" name="custom_classification" id="custom_classification" placeholder="e.g. Memory Dump, Network PCAP, RAM Dump..." class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                        <p class="text-[11px] text-blue-700">Specify your unique custom classification tag for this evidence item.</p>
                        @error('custom_classification') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <!-- Collection Date & Location -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="collected_at" class="block text-sm font-bold text-slate-800 mb-1">Collection Date & Time</label>
                        <input type="datetime-local" name="collected_at" id="collected_at" value="{{ date('Y-m-d\TH:i') }}" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="collected_location" class="block text-sm font-bold text-slate-800 mb-1">Collection Location / Scene</label>
                        <input type="text" name="collected_location" id="collected_location" required placeholder="e.g. Server Room B, Desk 4" class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Description -->
                <div>
                    <label for="description" class="block text-sm font-bold text-slate-800 mb-1">Evidence Description & Intake Notes</label>
                    <textarea name="description" id="description" rows="3" required class="w-full rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Describe acquisition method, cables attached..."></textarea>
                </div>

                <!-- File Upload -->
                <div>
                    <label for="evidence_file" class="block text-sm font-bold text-slate-800 mb-1">Evidence Payload File</label>
                    <input type="file" name="evidence_file" id="evidence_file" required class="w-full border border-slate-300 p-2 rounded-md text-sm bg-slate-50">
                    <p class="text-[11px] text-slate-500 mt-1">The vault will automatically compute an immutable SHA-256 cryptographic hash upon upload completion.</p>
                    @error('evidence_file') <p class="text-red-600 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex justify-end space-x-3 pt-3 border-t border-slate-200">
                    <a href="{{ route('cases.show', $case->id) }}" class="px-4 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white text-xs font-semibold rounded-md hover:bg-blue-700 transition shadow-sm">Upload & Compute SHA-256 Hash</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
