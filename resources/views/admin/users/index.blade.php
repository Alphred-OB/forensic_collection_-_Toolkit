<x-app-layout>
    <div class="py-6 space-y-6" x-data="{ showCreateModal: false, showEditModal: false, activeUser: {} }">
        <!-- Page Title Card inside content -->
        <div class="bg-white p-6 rounded-lg shadow-sm border border-slate-200">
            <h2 class="font-extrabold text-xl text-slate-900 leading-tight">
                User Account & Role Management
            </h2>
            <p class="text-xs text-slate-500 mt-1">Provision user accounts, edit details, assign system roles, or remove personnel access.</p>
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

        <!-- Action Bar & Compact Search -->
        <div class="bg-white p-4 rounded-lg shadow-sm border border-slate-200 flex flex-col md:flex-row items-center justify-between gap-4">
            <!-- Search & Role Filter Form (Max Width constrained) -->
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-1 items-center gap-2 max-w-xl">
                <input type="text" name="search" value="{{ $search }}" placeholder="Search by name or email..." class="w-64 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                <select name="role" class="w-40 rounded-md border-slate-300 shadow-sm text-sm focus:border-blue-500 focus:ring-blue-500">
                    <option value="">All Roles</option>
                    <option value="Administrator" {{ $role === 'Administrator' ? 'selected' : '' }}>Administrator</option>
                    <option value="Investigator" {{ $role === 'Investigator' ? 'selected' : '' }}>Investigator</option>
                    <option value="Reviewer" {{ $role === 'Reviewer' ? 'selected' : '' }}>Reviewer</option>
                </select>
                <button type="submit" class="px-3.5 py-2 bg-slate-900 text-white text-xs font-semibold rounded-md hover:bg-slate-800 transition">Filter</button>
                @if($search || $role)
                    <a href="{{ route('admin.users.index') }}" class="px-3 py-2 bg-slate-200 text-slate-700 text-xs font-semibold rounded-md hover:bg-slate-300 transition">Clear</a>
                @endif
            </form>

            <!-- Provision New User Button (Placed inside main page content area) -->
            <button @click="showCreateModal = true" class="px-4 py-2 bg-blue-600 text-white rounded-md text-xs font-bold uppercase tracking-wider hover:bg-blue-700 shadow-sm transition">
                + Provision New User Account
            </button>
        </div>

        <!-- User Table -->
        <div class="bg-white overflow-hidden shadow-sm rounded-lg border border-slate-200 p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Joined Date</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200 text-sm">
                        @foreach($users as $user)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2.5 py-1 inline-flex text-xs font-semibold rounded-full uppercase {{ $user->role === 'Administrator' ? 'bg-purple-100 text-purple-800' : ($user->role === 'Investigator' ? 'bg-blue-100 text-blue-800' : 'bg-slate-100 text-slate-800') }}">
                                        {{ $user->role }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">{{ $user->created_at->format('Y-m-d H:i') }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium space-x-3">
                                    <!-- Edit User Modal Trigger -->
                                    <button @click="activeUser = { id: {{ $user->id }}, name: '{{ $user->name }}', email: '{{ $user->email }}', role: '{{ $user->role }}' }; showEditModal = true" class="text-blue-600 hover:text-blue-900 font-semibold text-xs">
                                        Edit / Update
                                    </button>

                                    <!-- Delete User Action -->
                                    @if($user->id !== Auth::id())
                                        <form method="POST" action="{{ route('admin.users.delete', $user->id) }}" class="inline" onsubmit="return confirm('Are you sure you want to delete this user account?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-900 font-semibold text-xs">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $users->links() }}
            </div>
        </div>

        <!-- Create User Modal -->
        <div x-show="showCreateModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Provision New User Account</h3>
                <form method="POST" action="{{ route('admin.users.store') }}">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Full Name</label>
                        <input type="text" name="name" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" name="email" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Initial Password</label>
                        <input type="password" name="password" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700">Assigned System Role</label>
                        <select name="role" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                            <option value="Investigator">Investigator</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Reviewer">Reviewer / Auditor</option>
                        </select>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showCreateModal = false" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md text-sm font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">Create Account</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit User Modal (Full CRUD Update) -->
        <div x-show="showEditModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
            <div class="bg-white rounded-lg p-6 max-w-md w-full shadow-xl border border-slate-200">
                <h3 class="text-lg font-bold text-slate-900 mb-2">Edit User Account</h3>
                <form method="POST" :action="`/admin/users/${activeUser.id}`">
                    @csrf
                    @method('PUT')
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Full Name</label>
                        <input type="text" name="name" x-model="activeUser.name" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Email Address</label>
                        <input type="email" name="email" x-model="activeUser.email" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-slate-700">Assigned Role</label>
                        <select name="role" x-model="activeUser.role" required class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                            <option value="Investigator">Investigator</option>
                            <option value="Administrator">Administrator</option>
                            <option value="Reviewer">Reviewer / Auditor</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="block text-sm font-medium text-slate-700">Reset Password (leave blank to keep current)</label>
                        <input type="password" name="password" placeholder="New password..." class="mt-1 block w-full rounded-md border-slate-300 text-sm">
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-200 text-slate-700 rounded-md text-sm font-semibold">Cancel</button>
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md text-sm font-semibold hover:bg-blue-700">Update User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
