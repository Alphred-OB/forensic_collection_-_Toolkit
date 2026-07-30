<x-app-layout>
    <div class="py-6 space-y-6">
        <!-- Header & Storage Allocation Metrics -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-4 mb-4">
                <div>
                    <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                        Global Evidence Vault and Storage Inspector
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Centralized repository and physical storage allocation analysis across all investigation cases.</p>
                </div>
                <div class="text-right">
                    <span class="text-xs font-semibold uppercase text-slate-500 block">Total Vault Storage Used</span>
                    <span class="text-2xl font-extrabold text-slate-900">{{ number_format($totalStorageBytes / 1048576, 2) }} <span class="text-sm font-normal text-slate-500">MB</span></span>
                </div>
            </div>

            <!-- Footprint Breakdown Cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach($classificationBreakdown as $b)
                    <div class="p-3 bg-slate-50 rounded-md border border-slate-200">
                        <p class="text-[11px] font-bold text-slate-600 uppercase">{{ str_replace('_', ' ', $b->classification) }}</p>
                        <p class="text-lg font-extrabold text-slate-900 mt-1">{{ $b->item_count }} <span class="text-xs font-normal text-slate-500">Items</span></p>
                        <p class="text-[11px] text-blue-600 font-mono font-medium">{{ number_format($b->storage_bytes / 1048576, 2) }} MB</p>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Search & Classification Filter -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('admin.evidence.index') }}" class="flex flex-col md:flex-row items-center gap-3">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by SHA-256 Hash, Evidence #, File Name..." class="w-full md:w-80 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                
                <select name="classification" class="w-full md:w-56 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Classifications</option>
                    <option value="hard_drive_image" {{ $classification === 'hard_drive_image' ? 'selected' : '' }}>Hard Drive Image</option>
                    <option value="memory_dump" {{ $classification === 'memory_dump' ? 'selected' : '' }}>Memory Dump</option>
                    <option value="network_pcap" {{ $classification === 'network_pcap' ? 'selected' : '' }}>Network PCAP</option>
                    <option value="mobile_extraction" {{ $classification === 'mobile_extraction' ? 'selected' : '' }}>Mobile Extraction</option>
                    <option value="document" {{ $classification === 'document' ? 'selected' : '' }}>Document</option>
                    <option value="other" {{ $classification === 'other' ? 'selected' : '' }}>Other</option>
                </select>

                <button type="submit" class="px-4 py-2 bg-slate-900 text-white text-xs font-semibold rounded-md hover:bg-slate-800 transition">Filter</button>
                @if($search || $classification)
                    <a href="{{ route('admin.evidence.index') }}" class="px-3 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Clear</a>
                @endif
            </form>
        </div>

        <!-- Evidence Items Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200 p-6">
            @if($evidenceItems->isEmpty())
                <p class="text-slate-500 text-center py-8 text-xs">No matching digital evidence items found in global vault.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item ID</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Case Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Classification</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">File Name & Size</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">SHA-256 Hash</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Custodian</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-xs">
                            @foreach($evidenceItems as $item)
                                <tr>
                                    <td class="px-4 py-3.5 font-bold text-blue-600">
                                        <a href="{{ route('evidence.show', $item->id) }}" class="hover:underline">{{ $item->evidence_number }}</a>
                                    </td>
                                    <td class="px-4 py-3.5 font-medium text-slate-800">
                                        <a href="{{ route('cases.show', $item->case->id) }}" class="hover:underline">{{ $item->case->case_number }}</a>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded text-[10px] uppercase font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ str_replace('_', ' ', $item->classification) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-slate-800">
                                        <span class="block font-semibold text-slate-900 truncate max-w-xs">{{ $item->file_name }}</span>
                                        <span class="text-slate-500 text-[10px]">{{ number_format($item->file_size / 1024, 2) }} KB</span>
                                    </td>
                                    <td class="px-4 py-3.5 font-mono text-slate-700 select-all" title="{{ $item->file_hash_sha256 }}">
                                        <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200 font-semibold">{{ substr($item->file_hash_sha256, 0, 14) }}...</span>
                                    </td>
                                    <td class="px-4 py-3.5 text-slate-700 font-medium">
                                        {{ $item->currentCustodian->name }}
                                    </td>
                                    <td class="px-4 py-3.5 text-right font-medium">
                                        <a href="{{ route('evidence.show', $item->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold">Inspect &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $evidenceItems->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
