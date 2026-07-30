@props(['crumbs' => []])

@if(!empty($crumbs))
    <nav class="flex items-center space-x-2 text-xs font-medium text-slate-500 mb-4 bg-white px-4 py-2.5 rounded-lg border border-slate-200 shadow-xs">
        <a href="{{ route('cases.index') }}" class="text-slate-500 hover:text-blue-600 transition flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 00-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
            Dashboard
        </a>

        @foreach($crumbs as $crumb)
            <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
            @if(isset($crumb['url']) && !$loop->last)
                <a href="{{ $crumb['url'] }}" class="text-slate-600 hover:text-blue-600 font-semibold transition truncate max-w-xs">{{ $crumb['label'] }}</a>
            @else
                <span class="text-slate-900 font-bold truncate max-w-xs">{{ $crumb['label'] }}</span>
            @endif
        @endforeach
    </nav>
@endif
