<x-app-layout>
    <div class="py-6 space-y-6" x-data="{ showTeamModal: false }">
        <x-breadcrumbs :crumbs="[
            ['label' => 'Cases', 'url' => route('cases.index')],
            ['label' => $case->case_number . ': ' . $case->title]
        ]" />
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        @if(!$case->isEditable())
            <div class="bg-slate-900 border border-slate-800 text-slate-200 px-4 py-3 rounded-lg text-xs flex items-center justify-between shadow-sm">
                <div>
                    <span class="font-bold text-amber-400 uppercase tracking-wider">Case Status: {{ $case->status }} (Read-Only Mode)</span>
                    <p class="text-slate-400 text-[11px] mt-0.5">This case is {{ $case->status }}. Evidence upload and team modifications are locked to preserve legal audit integrity.</p>
                </div>
                <span class="px-2.5 py-1 bg-slate-800 text-slate-300 rounded font-mono font-semibold text-[10px] uppercase border border-slate-700">READ_ONLY_LOCKED</span>
            </div>
        @endif

        <!-- Case Title Banner & Action Controls (In Page Content) -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="space-y-1">
                <div class="flex items-center flex-wrap gap-2.5">
                    <h2 class="font-extrabold text-xl md:text-2xl text-slate-900 leading-tight">
                        {{ $case->case_number }}: {{ $case->title }}
                    </h2>
                    <span class="uppercase px-2.5 py-0.5 rounded-full text-[11px] font-bold tracking-wider inline-flex items-center {{ $case->status === 'open' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($case->status === 'archived' ? 'bg-slate-200 text-slate-800 border border-slate-300' : 'bg-slate-100 text-slate-700 border border-slate-200') }}">
                        {{ $case->status }}
                    </span>
                </div>
                <p class="text-xs text-slate-500">Created by {{ $case->creator->name }} on {{ $case->created_at->format('Y-m-d H:i') }}</p>
            </div>

            <!-- Page Action Buttons -->
            <div class="flex items-center flex-wrap gap-2 shrink-0">
                <a href="{{ route('reports.case', $case->id) }}" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 shadow-xs transition">
                    Export Court-Ready Case Report
                </a>
                @if(Auth::user()->role !== 'Reviewer')
                    @if($case->isEditable())
                        <a href="{{ route('cases.edit', $case->id) }}" class="inline-flex items-center px-3.5 py-2 bg-slate-100 text-slate-800 border border-slate-300 rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-200 shadow-xs transition">
                            Edit Case
                        </a>
                        <a href="{{ route('evidence.create', $case->id) }}" class="inline-flex items-center px-3.5 py-2 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-xs transition">
                            + Upload Evidence
                        </a>
                        <form method="POST" action="{{ route('cases.update-status', $case->id) }}" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="closed">
                            <button type="submit" onclick="return confirm('Close this case? It will prevent new evidence intake.');" class="inline-flex items-center px-3.5 py-2 bg-slate-900 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-xs transition">
                                Close Case
                            </button>
                        </form>
                    @elseif($case->status === 'closed')
                        <form method="POST" action="{{ route('cases.update-status', $case->id) }}" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="open">
                            <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 shadow-xs transition">
                                Re-Open Case
                            </button>
                        </form>
                        <form method="POST" action="{{ route('cases.update-status', $case->id) }}" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="archived">
                            <button type="submit" onclick="return confirm('Archive this case?');" class="inline-flex items-center px-3.5 py-2 bg-slate-700 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-xs transition">
                                Archive Case
                            </button>
                        </form>
                    @elseif($case->status === 'archived')
                        <form method="POST" action="{{ route('cases.update-status', $case->id) }}" class="inline">
                            @csrf
                            <input type="hidden" name="status" value="open">
                            <button type="submit" class="inline-flex items-center px-3.5 py-2 bg-emerald-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-emerald-700 shadow-xs transition">
                                Unarchive Case
                            </button>
                        </form>
                    @endif
                @endif

                @if(Auth::user()->role === 'Administrator')
                    <form method="POST" action="{{ route('cases.destroy', $case->id) }}" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" onclick="return confirm('PERMANENT ACTION: Are you sure you want to delete this entire case record?');" class="inline-flex items-center px-3.5 py-2 bg-rose-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-rose-700 shadow-xs transition">
                            Delete Case
                        </button>
                    </form>
                @endif
            </div>
        </div>

        <!-- Case Summary & Team Grid -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Case Overview -->
            <div class="md:col-span-2 bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-2">Case Overview</h3>
                <p class="text-slate-600 text-sm mb-4 leading-relaxed">{{ $case->description ?: 'No detailed background provided.' }}</p>
                
                <div class="flex items-center space-x-6 text-xs text-slate-500 border-t border-slate-200 pt-4">
                    <div>
                        <span class="font-bold text-slate-700">Priority Level:</span> 
                        <span class="px-2 py-0.5 rounded font-bold uppercase ml-1 {{ $case->priority === 'Critical' ? 'bg-red-100 text-red-800 border border-red-200' : ($case->priority === 'High' ? 'bg-amber-100 text-amber-800 border border-amber-200' : ($case->priority === 'Low' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-blue-50 text-blue-700 border border-blue-200')) }}">
                            {{ $case->priority }}
                        </span>
                    </div>
                    <div>
                        <span class="font-bold text-slate-700">Lifecycle Status:</span> 
                        <span class="uppercase font-semibold text-slate-900 ml-1">{{ $case->status }}</span>
                    </div>
                    <div>
                        <span class="font-bold text-slate-700">Evidence Logged:</span> 
                        <span class="font-semibold text-slate-900 ml-1">{{ $case->evidenceItems->count() }} items</span>
                    </div>
                </div>
                @if($case->tags)
                    <div class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-700">Classification Tags:</span>
                        <div class="flex flex-wrap gap-1">
                            @foreach(explode(',', $case->tags) as $tag)
                                <span class="bg-slate-100 text-slate-700 px-2 py-0.5 rounded text-[11px] font-semibold border border-slate-200">{{ trim($tag) }}</span>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <!-- Assigned Team Panel -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Assigned Team</h3>
                    @if(Auth::user()->role !== 'Reviewer' && $case->isEditable())
                        <button @click="showTeamModal = true" class="text-xs text-blue-600 font-bold hover:underline">Manage Team</button>
                    @endif
                </div>
                <ul class="space-y-2">
                    @foreach($case->assignedUsers as $teamMember)
                        <li class="flex items-center justify-between text-xs p-2.5 bg-slate-50 rounded-md border border-slate-200">
                            <span class="font-semibold text-slate-800">{{ $teamMember->name }}</span>
                            <span class="text-[10px] font-bold text-slate-600 uppercase px-2 py-0.5 bg-slate-200 rounded">{{ $teamMember->role }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>

        <!-- Evidence Vault Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200 p-6">
            <div class="flex justify-between items-center mb-4">
                <div>
                    <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Evidence Vault</h3>
                    <span class="text-xs text-slate-500">{{ $case->evidenceItems->count() }} Items Recorded</span>
                </div>
                @if($case->evidenceItems->isNotEmpty())
                    <a href="{{ route('evidence.batch-export', $case->id) }}" class="inline-flex items-center px-3 py-1.5 bg-slate-900 text-white rounded text-xs font-bold uppercase tracking-wider hover:bg-slate-800 shadow-xs transition">
                        Export Batch ZIP + Manifest
                    </a>
                @endif
            </div>

            @if($case->evidenceItems->isEmpty())
                <div class="text-center py-8 text-slate-500 border-2 border-dashed border-slate-200 rounded-lg">
                    <p class="mb-2 text-xs">No evidence items logged for this case yet.</p>
                    @if($case->isEditable() && Auth::user()->role !== 'Reviewer')
                        <a href="{{ route('evidence.create', $case->id) }}" class="text-blue-600 hover:underline text-xs font-bold">+ Add Evidence Item</a>
                    @endif
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Item ID</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Description</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Classification</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">SHA-256 Hash</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Current Custodian</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm">
                            @foreach($case->evidenceItems as $item)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                        <a href="{{ route('evidence.show', $item->id) }}" class="hover:underline">{{ $item->evidence_number }}</a>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-slate-900 font-medium max-w-xs truncate">{{ $item->description }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 py-0.5 rounded text-xs uppercase font-bold bg-slate-100 text-slate-700 border border-slate-200">
                                            {{ str_replace('_', ' ', $item->classification) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-xs font-mono text-slate-500" title="{{ $item->file_hash_sha256 }}">
                                        {{ substr($item->file_hash_sha256, 0, 16) }}...
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700">
                                        <span class="font-semibold">{{ $item->currentCustodian->name }}</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('evidence.show', $item->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold text-xs">Inspect &rarr;</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        <!-- Interactive Chronological Timeline Visualizer & Event Reconstruction Map -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200" x-data="{ timelineFilter: 'all' }">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-200 pb-4 mb-6">
                <div>
                    <h3 class="text-base font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Chronological Investigation Timeline & Event Map
                    </h3>
                    <p class="text-xs text-slate-500 mt-0.5">Reconstruct incident events, evidence collection, custody shifts, and shift notes</p>
                </div>
                <!-- Timeline Filter Buttons -->
                <div class="flex items-center gap-1.5 bg-slate-100 p-1 rounded-lg border border-slate-200 text-xs shrink-0 overflow-x-auto">
                    <button @click="timelineFilter = 'all'" :class="timelineFilter === 'all' ? 'bg-white text-slate-900 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">All Events ({{ $timelineEvents->count() }})</button>
                    <button @click="timelineFilter = 'case'" :class="timelineFilter === 'case' ? 'bg-white text-blue-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">Case</button>
                    <button @click="timelineFilter = 'evidence'" :class="timelineFilter === 'evidence' ? 'bg-white text-emerald-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">Evidence</button>
                    <button @click="timelineFilter = 'custody'" :class="timelineFilter === 'custody' ? 'bg-white text-indigo-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">Custody</button>
                    <button @click="timelineFilter = 'note'" :class="timelineFilter === 'note' ? 'bg-white text-amber-700 font-bold shadow-xs' : 'text-slate-600 hover:text-slate-900'" class="px-3 py-1 rounded-md transition">Notes</button>
                </div>
            </div>

            <!-- Vertical Timeline Axis -->
            <div class="relative border-l-2 border-slate-200 ml-4 sm:ml-6 space-y-6">
                @forelse($timelineEvents as $event)
                    <div x-show="timelineFilter === 'all' || timelineFilter === '{{ $event['category'] }}'" 
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 transform translate-y-0"
                         class="relative pl-6 sm:pl-8 group">
                        
                        <!-- Timeline Node Dot -->
                        <div class="absolute -left-2.5 top-1.5 w-5 h-5 rounded-full border-2 border-white shadow-xs flex items-center justify-center 
                            {{ $event['color'] === 'blue' ? 'bg-blue-600' : ($event['color'] === 'emerald' ? 'bg-emerald-600' : ($event['color'] === 'indigo' ? 'bg-indigo-600' : ($event['color'] === 'rose' ? 'bg-rose-600' : ($event['color'] === 'amber' ? 'bg-amber-500' : 'bg-purple-600')))) }}">
                        </div>

                        <!-- Card Content -->
                        <div class="p-4 rounded-lg bg-slate-50 border border-slate-200 group-hover:border-slate-300 group-hover:shadow-xs transition">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-1 mb-2">
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-0.5 text-[10px] font-bold uppercase rounded tracking-wider
                                        {{ $event['color'] === 'blue' ? 'bg-blue-100 text-blue-800 border border-blue-200' : ($event['color'] === 'emerald' ? 'bg-emerald-100 text-emerald-800 border border-emerald-200' : ($event['color'] === 'indigo' ? 'bg-indigo-100 text-indigo-800 border border-indigo-200' : ($event['color'] === 'rose' ? 'bg-rose-100 text-rose-800 border border-rose-200' : ($event['color'] === 'amber' ? 'bg-amber-100 text-amber-900 border border-amber-200' : 'bg-purple-100 text-purple-800 border border-purple-200')))) }}">
                                        {{ $event['type_label'] }}
                                    </span>
                                    <h4 class="font-bold text-sm text-slate-900">{{ $event['title'] }}</h4>
                                </div>
                                <span class="font-mono text-xs text-slate-500 font-semibold">{{ \Carbon\Carbon::parse($event['timestamp'])->format('Y-m-d H:i:s T') }}</span>
                            </div>

                            <p class="text-xs text-slate-700 leading-relaxed mb-2">{{ $event['description'] }}</p>
                            
                            <div class="flex items-center justify-between text-[11px] text-slate-500 border-t border-slate-200/60 pt-2 mt-2">
                                <span>Recorded by: <strong class="text-slate-800">{{ $event['actor'] }}</strong></span>
                                <span class="font-mono text-[10px]">{{ \Carbon\Carbon::parse($event['timestamp'])->diffForHumans() }}</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-500 py-6 text-center">No timeline events recorded yet.</p>
                @endforelse
            </div>
        </div>

        <!-- Case Activity & Operational Shift Notes (Split Grid) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Operational Shift Notes Thread -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200 flex flex-col justify-between">
                <div>
                    <div class="flex items-center justify-between border-b border-slate-200 pb-3 mb-4">
                        <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider">Case Operational Notes</h3>
                        <span class="text-xs text-slate-500 font-semibold">{{ $case->notes->count() }} Entries</span>
                    </div>

                    <!-- Add New Shift Note Form -->
                    @if($case->isEditable() && Auth::user()->role !== 'Reviewer')
                        <form method="POST" action="{{ route('cases.notes.store', $case->id) }}" class="mb-4">
                            @csrf
                            <div>
                                <textarea name="note" rows="2" required placeholder="Log operational shift note, interview detail, or handover instruction..." class="w-full rounded-md border-slate-300 text-xs focus:border-blue-500 focus:ring-blue-500"></textarea>
                            </div>
                            <div class="flex items-center justify-between mt-2">
                                <label class="inline-flex items-center text-xs text-slate-600">
                                    <input type="checkbox" name="is_pinned" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ms-1.5 font-medium">Pin to top of case</span>
                                </label>
                                <button type="submit" class="px-3.5 py-1.5 bg-slate-900 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-slate-800 transition">
                                    Post Note
                                </button>
                            </div>
                        </form>
                    @endif

                    <!-- Notes Thread List -->
                    <div class="space-y-3 max-h-80 overflow-y-auto">
                        @forelse($case->notes as $note)
                            <div class="p-3 rounded-lg border {{ $note->is_pinned ? 'bg-amber-50/70 border-amber-300' : 'bg-slate-50 border-slate-200' }}">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="font-bold text-xs text-slate-900">{{ $note->user->name }} <span class="text-[10px] text-blue-600 font-semibold">({{ $note->user->role }})</span></span>
                                    <div class="flex items-center space-x-2 text-[10px] text-slate-500 font-mono">
                                        @if($note->is_pinned)
                                            <span class="px-1.5 py-0.5 bg-amber-200 text-amber-900 font-bold uppercase rounded text-[9px]">Pinned</span>
                                        @endif
                                        <span>{{ $note->created_at->format('Y-m-d H:i') }}</span>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $note->note }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-500 py-4 text-center">No operational shift notes recorded yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Recent Case Activity Audit Timeline -->
            <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
                <h3 class="text-sm font-bold text-slate-900 uppercase tracking-wider mb-4 border-b border-slate-200 pb-3">Case Activity & Audit Trail</h3>
                @if($activityLogs->isEmpty())
                    <p class="text-xs text-slate-500">No activity logged for this case yet.</p>
                @else
                    <ul class="divide-y divide-slate-200 text-xs">
                        @foreach($activityLogs as $log)
                            <li class="py-2.5 flex justify-between items-center">
                                <div>
                                    <span class="font-bold text-slate-900">{{ $log->user ? $log->user->name : 'System' }}</span>
                                    <span class="font-mono text-slate-700 uppercase px-2 py-0.5 bg-slate-100 rounded ml-2 border border-slate-200">{{ $log->action_type }}</span>
                                </div>
                                <span class="text-slate-500 font-mono">{{ $log->created_at->format('Y-m-d H:i:s') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        <!-- Manage Team Modal -->
        <div x-show="showTeamModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Manage Assigned Team</h3>
                <form method="POST" action="{{ route('cases.update-team', $case->id) }}">
                    @csrf
                    <div class="space-y-2 max-h-60 overflow-y-auto mb-4 p-3 border border-slate-200 rounded-md">
                        @foreach($allUsers as $user)
                            <div class="flex items-center">
                                <input type="checkbox" name="assigned_users[]" value="{{ $user->id }}" id="modal_user_{{ $user->id }}" {{ $case->assignedUsers->contains($user->id) ? 'checked' : '' }} class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <label for="modal_user_{{ $user->id }}" class="ms-2 text-xs font-medium text-slate-700">{{ $user->name }} ({{ $user->role }})</label>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showTeamModal = false" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md text-xs font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-xs font-semibold hover:bg-blue-700">Save Assignments</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
