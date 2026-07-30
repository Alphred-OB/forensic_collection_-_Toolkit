<x-app-layout>
    <div class="py-6 space-y-6">
        <!-- Scan Results Flash Modal Alert (if scan was just performed) -->
        @if(session('scan_results'))
            @php $res = session('scan_results'); @endphp
            <div class="p-5 rounded-lg border bg-white shadow-sm border-slate-200 space-y-3">
                <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                    <h3 class="font-bold text-sm text-slate-900 uppercase tracking-wide">System Diagnostic Scan Results</h3>
                    <span class="text-xs text-slate-500 font-mono">Scanned at: {{ $res['scanned_at'] }}</span>
                </div>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-center">
                    <div class="p-3 bg-slate-50 rounded border border-slate-200">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">Audit Chain Status</p>
                        <p class="text-sm font-bold mt-1 {{ $res['chain_valid'] ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $res['chain_valid'] ? 'Chain Valid' : 'Tampering Detected' }}
                        </p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded border border-slate-200">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">Audit Log Records</p>
                        <p class="text-sm font-bold text-slate-900 mt-1">{{ $res['total_audit_records'] }} Verified</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded border border-slate-200">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">Disk Files Scanned</p>
                        <p class="text-sm font-bold text-slate-900 mt-1">{{ $res['total_evidence_files'] }} Files</p>
                    </div>
                    <div class="p-3 bg-slate-50 rounded border border-slate-200">
                        <p class="text-[11px] font-semibold text-slate-500 uppercase">File Corruption Alert</p>
                        <p class="text-sm font-bold mt-1 {{ $res['corrupted_evidence_files'] === 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $res['corrupted_evidence_files'] }} Corrupted
                        </p>
                    </div>
                </div>

                @if(!empty($res['corrupted_items']))
                    <div class="p-3 bg-red-50 border border-red-200 rounded text-xs text-red-800">
                        <p class="font-bold mb-1">Corrupted Evidence Items Flagged:</p>
                        <ul class="list-disc list-inside">
                            @foreach($res['corrupted_items'] as $corrupted)
                                <li>Evidence #{{ $corrupted['number'] }} (File: {{ $corrupted['file'] }})</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        @endif

        <!-- Cryptographic Chain Integrity Banner & Action Bar -->
        <div class="p-5 rounded-lg border {{ $verificationResult['is_valid'] ? 'bg-emerald-50 border-emerald-300 text-emerald-900' : 'bg-red-50 border-red-300 text-red-900' }}">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-2">
                        <h4 class="font-bold text-sm uppercase tracking-wide">
                            {{ $verificationResult['is_valid'] ? 'Cryptographic Hash Chain Verified' : 'CRITICAL ALERT: Audit Log Tampering Detected!' }}
                        </h4>
                    </div>
                    <p class="text-xs mt-1 text-slate-600">
                        Successfully verified {{ $verificationResult['total_entries'] }} sequential hash-chained audit log records. No database tampering or record deletion detected.
                    </p>
                </div>
                
                <!-- Action Controls for Admin -->
                <div class="flex items-center space-x-2 shrink-0">
                    @if(Auth::user()->role === 'Administrator')
                        <!-- Run System Scan Button -->
                        <form method="POST" action="{{ route('admin.audit.scan') }}" class="inline">
                            @csrf
                            <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-xs transition">
                                Run Deep System Scan
                            </button>
                        </form>

                        <!-- Export CSV Button -->
                        <a href="{{ route('admin.audit.export-csv') }}" class="px-3.5 py-1.5 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-xs transition">
                            Export Audit CSV
                        </a>
                    @endif

                    <span class="px-3.5 py-1.5 text-xs font-mono font-bold rounded-full uppercase tracking-wider {{ $verificationResult['is_valid'] ? 'bg-emerald-200 text-emerald-900 border border-emerald-300' : 'bg-red-200 text-red-900 border border-red-300' }}">
                        {{ $verificationResult['is_valid'] ? 'Status: Intact' : 'Status: Tampered' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Multi-Dimensional Search & Filter Bar -->
        <div class="bg-white p-5 rounded-lg shadow-sm border border-slate-200">
            <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-5 gap-3 items-end">
                <!-- Search Query -->
                <div>
                    <label for="search" class="block text-xs font-bold text-slate-700 uppercase mb-1">Search Keywords</label>
                    <input type="text" name="search" id="search" value="{{ $search }}" placeholder="Search action, hash, details..." class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Action Type Filter -->
                <div>
                    <label for="action" class="block text-xs font-bold text-slate-700 uppercase mb-1">Action Event Category</label>
                    <select name="action" id="action" class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Action Types</option>
                        <option value="create" {{ $action === 'create' ? 'selected' : '' }}>Create / Upload</option>
                        <option value="edit" {{ $action === 'edit' ? 'selected' : '' }}>Edit / Updates</option>
                        <option value="delete" {{ $action === 'delete' ? 'selected' : '' }}>Deletions (Soft Delete)</option>
                        <option value="view" {{ $action === 'view' ? 'selected' : '' }}>Views / Inspections</option>
                        <option value="download_evidence" {{ $action === 'download_evidence' ? 'selected' : '' }}>File Downloads</option>
                    </select>
                </div>

                <!-- Personnel Filter -->
                <div>
                    <label for="user_id" class="block text-xs font-bold text-slate-700 uppercase mb-1">Personnel / User</label>
                    <select name="user_id" id="user_id" class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                        <option value="">All Personnel</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ (string)$userId === (string)$user->id ? 'selected' : '' }}>{{ $user->name }} ({{ $user->role }})</option>
                        @endforeach
                    </select>
                </div>

                <!-- Date Range From -->
                <div>
                    <label for="date_from" class="block text-xs font-bold text-slate-700 uppercase mb-1">From Date</label>
                    <input type="date" name="date_from" id="date_from" value="{{ $dateFrom }}" class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                </div>

                <!-- Date Range To & Action Buttons -->
                <div class="flex items-center space-x-2">
                    <div class="flex-1">
                        <label for="date_to" class="block text-xs font-bold text-slate-700 uppercase mb-1">To Date</label>
                        <input type="date" name="date_to" id="date_to" value="{{ $dateTo }}" class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500">
                    </div>
                    <div class="flex items-center space-x-1 shrink-0">
                        <button type="submit" class="px-3 py-2 bg-slate-900 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 transition">
                            Filter
                        </button>
                        @if($search || $action || $userId || $dateFrom || $dateTo)
                            <a href="{{ route('audit.index') }}" class="px-2.5 py-2 bg-slate-200 text-slate-700 rounded-md text-xs font-bold hover:bg-slate-300 transition">
                                Clear
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200 p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Log ID</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Action Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Target Entity</th>
                            
                            <!-- Current Record Hash with Hover Tooltip -->
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <div class="group relative inline-flex items-center gap-1 cursor-help">
                                    <span>Current Record Hash</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    
                                    <!-- Tooltip content -->
                                    <div class="pointer-events-none absolute left-0 top-full mt-1.5 hidden group-hover:block w-72 p-3 bg-slate-900 text-white text-[11px] rounded-lg shadow-xl z-50 normal-case font-normal leading-normal">
                                        <p class="font-bold text-blue-400 mb-1">SHA-256 Entry Fingerprint</p>
                                        A unique 64-character SHA-256 cryptographic hash calculated from this record's payload + timestamp. Proves row content hasn't been edited.
                                    </div>
                                </div>
                            </th>

                            <!-- Linked Previous Hash with Hover Tooltip -->
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">
                                <div class="group relative inline-flex items-center gap-1 cursor-help">
                                    <span>Linked Previous Hash</span>
                                    <svg class="w-3.5 h-3.5 text-slate-400 group-hover:text-blue-600 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    
                                    <!-- Tooltip content -->
                                    <div class="pointer-events-none absolute left-0 top-full mt-1.5 hidden group-hover:block w-72 p-3 bg-slate-900 text-white text-[11px] rounded-lg shadow-xl z-50 normal-case font-normal leading-normal">
                                        <p class="font-bold text-blue-400 mb-1">Hash Chain Link</p>
                                        Stores the SHA-256 hash of the preceding log row. Links all logs together so deleting or inserting rows instantly breaks verification.
                                    </div>
                                </div>
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Timestamp</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 text-xs">
                        @foreach($logs as $log)
                            <tr>
                                <td class="px-4 py-3.5 font-bold text-slate-700">#{{ $log->id }}</td>
                                <td class="px-4 py-3.5 font-semibold text-slate-900">{{ $log->user ? $log->user->name : 'System' }}</td>
                                <td class="px-4 py-3.5">
                                    <span class="px-2.5 py-1 rounded font-mono text-[11px] font-bold uppercase bg-slate-100 text-slate-800 border border-slate-200">
                                        {{ $log->action_type }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 text-slate-700 font-medium">
                                    {{ $log->target_type ? class_basename($log->target_type) . ' #' . $log->target_id : '-' }}
                                </td>

                                <!-- Current Record Hash Pill with Full Hash Hover Tooltip -->
                                <td class="px-4 py-3.5 font-mono text-slate-900">
                                    <div class="group relative inline-block cursor-pointer">
                                        <span class="bg-slate-100 text-slate-800 px-2 py-0.5 rounded border border-slate-200 font-semibold group-hover:border-blue-500 transition">
                                            {{ substr($log->entry_hash, 0, 14) }}...
                                        </span>
                                        <!-- Full Hash Tooltip -->
                                        <div class="pointer-events-none absolute left-0 bottom-full mb-1.5 hidden group-hover:block w-auto max-w-xs p-2 bg-slate-900 text-emerald-400 font-mono text-[10px] rounded shadow-lg z-50 break-all">
                                            <span class="text-slate-400 block text-[9px] uppercase font-sans font-bold">Full SHA-256 Hash:</span>
                                            {{ $log->entry_hash }}
                                        </div>
                                    </div>
                                </td>

                                <!-- Linked Previous Hash Pill with Full Hash Hover Tooltip -->
                                <td class="px-4 py-3.5 font-mono text-slate-500">
                                    <div class="group relative inline-block cursor-pointer">
                                        <span class="bg-slate-50 text-slate-500 px-2 py-0.5 rounded border border-slate-200 group-hover:border-blue-500 transition">
                                            {{ substr($log->previous_entry_hash, 0, 14) }}...
                                        </span>
                                        <!-- Full Hash Tooltip -->
                                        <div class="pointer-events-none absolute left-0 bottom-full mb-1.5 hidden group-hover:block w-auto max-w-xs p-2 bg-slate-900 text-slate-300 font-mono text-[10px] rounded shadow-lg z-50 break-all">
                                            <span class="text-slate-400 block text-[9px] uppercase font-sans font-bold">Full Linked Hash:</span>
                                            {{ $log->previous_entry_hash }}
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-3.5 text-slate-500 whitespace-nowrap font-mono text-[11px]">{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $logs->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
