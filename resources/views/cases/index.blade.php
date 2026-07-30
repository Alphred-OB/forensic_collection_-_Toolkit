<x-app-layout>
    <div class="py-6 space-y-6">
        <!-- Page Title Card inside content -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                Forensic Cases Management
            </h2>
            <p class="text-xs text-slate-500 mt-1">Manage digital evidence investigation cases, team assignments, and lifecycle status.</p>
        </div>
        @if (session('success'))
            <div class="bg-emerald-50 border border-emerald-300 text-emerald-800 px-4 py-3 rounded-lg text-sm font-medium">
                {{ session('success') }}
            </div>
        @endif

        <!-- Stats Bar (System Slate & Emerald Accent Palette) -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('cases.index') }}" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 border-l-4 border-l-slate-900 hover:bg-slate-50 transition">
                <p class="text-xs font-semibold text-slate-500 uppercase">Total Cases</p>
                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total'] }}</p>
            </a>
            <a href="{{ route('cases.index', ['status' => 'open']) }}" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 border-l-4 border-l-emerald-600 hover:bg-slate-50 transition">
                <p class="text-xs font-semibold text-slate-500 uppercase">Active Open</p>
                <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['open'] }}</p>
            </a>
            <a href="{{ route('cases.index', ['status' => 'closed']) }}" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 border-l-4 border-l-slate-400 hover:bg-slate-50 transition">
                <p class="text-xs font-semibold text-slate-500 uppercase">Closed</p>
                <p class="text-2xl font-bold text-slate-700 mt-1">{{ $stats['closed'] }}</p>
            </a>
            <a href="{{ route('cases.index', ['status' => 'archived']) }}" class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 border-l-4 border-l-slate-500 hover:bg-slate-50 transition">
                <p class="text-xs font-semibold text-slate-500 uppercase">Archived</p>
                <p class="text-2xl font-bold text-slate-600 mt-1">{{ $stats['archived'] }}</p>
            </a>
        </div>

        <!-- Search & Filter Bar + Page Content Add Button -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
            <form method="GET" action="{{ route('cases.index') }}" class="flex flex-1 items-center gap-2 max-w-xl">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by Case #, Title, or Tags..." class="w-64 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="status" class="w-40 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Statuses</option>
                    <option value="open" {{ $status === 'open' ? 'selected' : '' }}>Open</option>
                    <option value="closed" {{ $status === 'closed' ? 'selected' : '' }}>Closed</option>
                    <option value="archived" {{ $status === 'archived' ? 'selected' : '' }}>Archived</option>
                    @if(Auth::user()->role === 'Administrator')
                        <option value="deleted" {{ $status === 'deleted' ? 'selected' : '' }}>Deleted (Soft Deleted)</option>
                    @endif
                </select>
                <button type="submit" class="px-3.5 py-2 bg-slate-900 text-white text-xs font-semibold rounded-md hover:bg-slate-800 transition">Filter</button>
                @if($search || $status)
                    <a href="{{ route('cases.index') }}" class="px-3 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Clear</a>
                @endif
            </form>

            @if(Auth::user()->role !== 'Reviewer')
                <a href="{{ route('cases.create') }}" class="px-4 py-2 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-sm transition">
                    + Create New Case
                </a>
            @endif
        </div>

        <!-- Cases Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200 p-6">
            @if($cases->isEmpty())
                <p class="text-slate-500 text-center py-8">No matching cases found.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Case Number</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Priority</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Title & Classification Tags</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Assigned Team</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Created Date</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-slate-200 text-sm">
                            @foreach($cases as $case)
                                <tr class="{{ $case->trashed() ? 'bg-rose-50/50' : '' }}">
                                    <td class="px-4 py-4 whitespace-nowrap text-sm font-bold text-blue-600">
                                        @if(!$case->trashed())
                                            <a href="{{ route('cases.show', $case->id) }}" class="hover:underline">{{ $case->case_number }}</a>
                                        @else
                                            <span class="text-slate-500 line-through">{{ $case->case_number }}</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs">
                                        <span class="px-2 py-0.5 rounded font-bold uppercase {{ $case->priority === 'Critical' ? 'bg-red-100 text-red-800 border border-red-200' : ($case->priority === 'High' ? 'bg-amber-100 text-amber-800 border border-amber-200' : ($case->priority === 'Low' ? 'bg-slate-100 text-slate-600 border border-slate-200' : 'bg-blue-50 text-blue-700 border border-blue-200')) }}">
                                            {{ $case->priority }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-900 font-medium">
                                        <div class="font-bold text-slate-900 {{ $case->trashed() ? 'line-through text-slate-500' : '' }}">{{ $case->title }}</div>
                                        @if($case->tags)
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach(explode(',', $case->tags) as $tag)
                                                    <span class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded text-[10px] font-semibold border border-slate-200">{{ trim($tag) }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-sm">
                                        @if($case->trashed())
                                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full uppercase bg-rose-100 text-rose-800 border border-rose-200">
                                                DELETED
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full uppercase {{ $case->status === 'open' ? 'bg-emerald-100 text-emerald-800' : ($case->status === 'archived' ? 'bg-slate-200 text-slate-800' : 'bg-slate-100 text-slate-700') }}">
                                                {{ strtoupper($case->status) }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-sm text-slate-600">
                                        <span class="font-medium text-slate-800">{{ $case->assignedUsers->pluck('name')->join(', ') }}</span>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap text-xs text-slate-500">{{ $case->created_at->format('Y-m-d H:i') }}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-3">
                                            @if(!$case->trashed())
                                                <a href="{{ route('cases.show', $case->id) }}" class="text-blue-600 hover:text-blue-900 font-semibold text-xs">View Case &rarr;</a>
                                                @if($case->isEditable() && Auth::user()->role !== 'Reviewer')
                                                    <a href="{{ route('cases.edit', $case->id) }}" class="text-slate-600 hover:text-slate-900 font-semibold text-xs">Edit</a>
                                                @endif
                                                @if(Auth::user()->role === 'Administrator')
                                                    <form method="POST" action="{{ route('cases.destroy', $case->id) }}" class="inline" onsubmit="return confirm('Soft-delete this case? It can be restored later.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="text-rose-600 hover:text-rose-900 font-semibold text-xs">Delete</button>
                                                    </form>
                                                @endif
                                            @else
                                                @if(Auth::user()->role === 'Administrator')
                                                    <form method="POST" action="{{ route('cases.restore', $case->id) }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="text-emerald-600 hover:text-emerald-800 font-bold text-xs uppercase bg-emerald-50 px-2 py-1 rounded border border-emerald-200">Restore Case</button>
                                                    </form>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $cases->links() }}
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
