<aside class="w-64 bg-slate-900 text-slate-300 flex flex-col shrink-0 h-screen sticky top-0 border-r border-slate-800 overflow-y-auto z-40">
    <!-- Brand / Logo Header -->
    <div class="p-5 border-b border-slate-800 flex items-center space-x-3">
        <img src="{{ asset('favicon/favicon.svg') }}" alt="Forensic Toolkit Logo" class="w-8 h-8 rounded-md shadow-sm" />
        <div>
            <h1 class="font-bold text-white text-xs tracking-wider">FORENSIC TOOLKIT</h1>
            <p class="text-[11px] text-blue-400 font-medium">Admin Console</p>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 p-4 space-y-1.5 text-sm font-medium">
        <div class="px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            Main Management
        </div>

        <a href="{{ route('admin.dashboard') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('admin.dashboard') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path></svg>
            Dashboard Overview
        </a>

        <a href="{{ route('admin.users.index') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('admin.users.*') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            User Accounts & Roles
        </a>

        <div class="pt-5 px-3 py-2 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            System & Operations
        </div>

        <a href="{{ route('cases.index') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('cases.*') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"></path></svg>
            Cases Repository
        </a>

        <a href="{{ route('admin.evidence.index') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('admin.evidence.*') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 01-2-2V5a2 2 0 012-2h14a2 2 0 012 2v1a2 2 0 01-2 2M5 8v10a2 2 0 002 2h14a2 2 0 002-2V8m-9 4h4"></path></svg>
            Global Evidence Vault
        </a>

        <a href="{{ route('audit.index') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('audit.*') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
            Audit Trail & Hashes
        </a>

        <a href="{{ route('admin.settings.index') }}" class="flex items-center px-3 py-2.5 rounded-md transition {{ request()->routeIs('admin.settings.*') ? 'bg-blue-600 text-white font-semibold shadow-sm' : 'hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
            System & Vault Settings
        </a>
    </nav>

    <!-- User Profile & Logout Footer -->
    <div class="p-4 border-t border-slate-800 bg-slate-950/60">
        <div class="flex items-center justify-between">
            <div class="truncate mr-2">
                <p class="text-xs font-semibold text-white truncate">{{ Auth::user()->name }}</p>
                <p class="text-[11px] text-blue-400 font-medium">{{ Auth::user()->role }}</p>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" title="Log Out" class="p-2 text-slate-400 hover:text-white hover:bg-slate-800 rounded-md transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </button>
            </form>
        </div>
    </div>
</aside>
