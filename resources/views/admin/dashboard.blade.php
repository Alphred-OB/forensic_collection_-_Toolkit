<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-extrabold text-lg text-slate-900 leading-tight">
                System Administrator Console
            </h2>
            <p class="text-xs text-slate-500">Overview of system health, evidence vault storage, and security audit logs.</p>
        </div>
    </x-slot>

    <div class="py-6 space-y-6">
        <!-- Quick Action Banner -->
        <div class="flex justify-end">
            <a href="{{ route('admin.users.index') }}" class="px-3.5 py-2 bg-slate-900 text-white text-xs font-bold uppercase tracking-wider rounded-md shadow-xs hover:bg-slate-800 transition">
                Manage User Accounts &rarr;
            </a>
        </div>
        <!-- Integrity & Storage Banner -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Hash Chain Status -->
            <div class="p-6 rounded-lg shadow-sm border bg-slate-900 text-white border-slate-800">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">System Integrity Scan</span>
                    <span class="px-2.5 py-1 rounded text-xs font-bold uppercase {{ $auditIntegrity['is_valid'] ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30' : 'bg-red-500/20 text-red-300 border border-red-500/30' }}">
                        {{ $auditIntegrity['is_valid'] ? 'Verified Healthy' : 'Corrupted' }}
                    </span>
                </div>
                <p class="text-3xl font-extrabold">{{ $auditIntegrity['total_entries'] }}</p>
                <p class="text-xs text-slate-400 mt-1">Verified Sequential Audit Hash Entries</p>
            </div>

            <!-- Storage Vault Usage -->
            <div class="p-6 bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">Vault Evidence Storage</span>
                    <span class="text-xs font-semibold text-slate-700 uppercase">{{ $stats['total_evidence'] }} Files</span>
                </div>
                <p class="text-3xl font-extrabold text-slate-900">{{ number_format($stats['total_storage_bytes'] / 1048576, 2) }} <span class="text-sm font-normal text-slate-500">MB</span></p>
                <p class="text-xs text-slate-500 mt-1">Total Digital Artifacts Encrypted on Disk</p>
            </div>

            <!-- Active Cases -->
            <div class="p-6 bg-white rounded-lg shadow-sm border border-slate-200">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold uppercase text-slate-500">Investigation Lifecycle</span>
                    <span class="text-xs font-semibold text-emerald-600 uppercase">{{ $stats['open_cases'] }} Active Open</span>
                </div>
                <p class="text-3xl font-extrabold text-slate-900">{{ $stats['total_cases'] }} <span class="text-sm font-normal text-slate-500">Cases</span></p>
                <p class="text-xs text-slate-500 mt-1">Total System Cases Managed</p>
            </div>
        </div>

        <!-- Role Distribution Bar -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h3 class="text-sm font-bold text-slate-900 mb-4">User Personnel Directory</h3>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Administrators</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['admins'] }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Investigators</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['investigators'] }}</p>
                </div>
                <div class="p-4 bg-slate-50 rounded-lg border border-slate-200">
                    <p class="text-xs font-semibold text-slate-500 uppercase">Reviewers / Auditors</p>
                    <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['reviewers'] }}</p>
                </div>
            </div>
        </div>

        <!-- Interactive Analytics Charts Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- 1. Case Status Breakdown (Doughnut Chart) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-1">Case Status Distribution</h3>
                    <p class="text-xs text-slate-500 mb-4">Active vs Closed & Archived Cases</p>
                </div>
                <div class="relative h-48 w-full flex items-center justify-center">
                    <canvas id="caseStatusChart"></canvas>
                </div>
            </div>

            <!-- 2. Evidence Classification Vault Breakdown (Bar Chart) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-1">Evidence Artifact Vault</h3>
                    <p class="text-xs text-slate-500 mb-4">Distribution by Classification Type</p>
                </div>
                <div class="relative h-48 w-full flex items-center justify-center">
                    <canvas id="evidenceClassificationChart"></canvas>
                </div>
            </div>

            <!-- 3. Audit Activity Trend (Line Chart) -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-1">Audit Volume Velocity</h3>
                    <p class="text-xs text-slate-500 mb-4">Historical Audit Log Entry Volume</p>
                </div>
                <div class="relative h-48 w-full flex items-center justify-center">
                    <canvas id="auditTrendChart"></canvas>
                </div>
            </div>
        </div>

        <!-- System Audit Feed & User List Split -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Recent Audit Entries -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4 border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Recent Security Audit Feed</h3>
                    <a href="{{ route('audit.index') }}" class="text-xs text-slate-500 hover:text-slate-900 font-semibold hover:underline">View Full Log &rarr;</a>
                </div>
                <ul class="divide-y divide-slate-200 text-xs">
                    @foreach($recentAuditLogs as $log)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <span class="font-semibold text-slate-900">{{ $log->user ? $log->user->name : 'System' }}</span>
                                <span class="text-[11px] font-mono uppercase px-2 py-0.5 bg-slate-100 text-slate-700 rounded ml-2 border border-slate-200">{{ $log->action_type }}</span>
                            </div>
                            <span class="font-mono text-slate-500 select-all" title="{{ $log->entry_hash }}">{{ substr($log->entry_hash, 0, 10) }}...</span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <!-- Recent Registered Users -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-4 border-b border-slate-200 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Active User Accounts</h3>
                    <a href="{{ route('admin.users.index') }}" class="text-xs text-slate-500 hover:text-slate-900 font-semibold hover:underline">Manage Accounts &rarr;</a>
                </div>
                <ul class="divide-y divide-slate-200 text-xs">
                    @foreach($recentUsers as $u)
                        <li class="py-3 flex justify-between items-center">
                            <div>
                                <p class="font-semibold text-slate-900">{{ $u->name }}</p>
                                <p class="text-slate-500 text-xs">{{ $u->email }}</p>
                            </div>
                            <span class="px-2.5 py-1 text-xs font-medium rounded-full uppercase bg-slate-100 text-slate-800 border border-slate-200">{{ $u->role }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Chart.js Script Initializer -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Case Status Doughnut Chart
            new Chart(document.getElementById('caseStatusChart'), {
                type: 'doughnut',
                data: {
                    labels: ['Open', 'Closed', 'Archived'],
                    datasets: [{
                        data: [
                            {{ $caseStatusChart['open'] }},
                            {{ $caseStatusChart['closed'] }},
                            {{ $caseStatusChart['archived'] }}
                        ],
                        backgroundColor: ['#10b981', '#64748b', '#cbd5e1'],
                        borderWidth: 2,
                        borderColor: '#ffffff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'bottom', labels: { font: { size: 11, family: 'sans-serif' } } }
                    }
                }
            });

            // 2. Evidence Classification Bar Chart
            const classificationLabels = {!! json_encode(array_map(fn($k) => strtoupper(str_replace('_', ' ', $k)), array_keys($evidenceClassificationChart))) !!};
            const classificationCounts = {!! json_encode(array_values($evidenceClassificationChart)) !!};

            new Chart(document.getElementById('evidenceClassificationChart'), {
                type: 'bar',
                data: {
                    labels: classificationLabels.length ? classificationLabels : ['No Evidence'],
                    datasets: [{
                        label: 'Item Count',
                        data: classificationCounts.length ? classificationCounts : [0],
                        backgroundColor: '#2563eb',
                        borderRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } }
                    }
                }
            });

            // 3. Audit Trend Line Chart
            const auditLabels = {!! json_encode(array_keys($auditTrendChart)) !!};
            const auditCounts = {!! json_encode(array_values($auditTrendChart)) !!};

            new Chart(document.getElementById('auditTrendChart'), {
                type: 'line',
                data: {
                    labels: auditLabels.length ? auditLabels : ['Current'],
                    datasets: [{
                        label: 'Audit Log Entries',
                        data: auditCounts.length ? auditCounts : [{{ $auditIntegrity['total_entries'] }}],
                        borderColor: '#4f46e5',
                        backgroundColor: 'rgba(79, 70, 229, 0.1)',
                        fill: true,
                        tension: 0.3,
                        borderWidth: 2,
                        pointRadius: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } },
                    scales: {
                        y: { beginAtZero: true, ticks: { precision: 0, font: { size: 10 } } },
                        x: { ticks: { font: { size: 10 } } }
                    }
                }
            });
        });
    </script>
</x-app-layout>
