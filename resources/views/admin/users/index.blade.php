@extends('layouts.app')

@section('title', 'User Management')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-lg">
        <!-- Header -->
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">User Management</h1>
                    <p class="text-gray-600">Manage LIMS users and their access levels</p>
                </div>
                <a href="{{ route('admin.users.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Add New User
                </a>
            </div>
        </div>

        <!-- Filters -->
        <div class="px-6 py-4 bg-gray-50 border-b">
            <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-wrap gap-4">
                <div class="flex-1 min-w-64">
                    <input type="text" name="search" value="{{ request('search') }}" 
                           placeholder="Search by name or email..."
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                <div>
                    <select name="user_type" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All User Types</option>
                        @foreach(\App\Models\User::USER_TYPES as $key => $label)
                            <option value="{{ $key }}" {{ request('user_type') === $key ? 'selected' : '' }}>
                                {{ $label }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="status" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <button type="submit" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                    Filter
                </button>
                @if(request()->anyFilled(['search', 'user_type', 'status']))
                    <a href="{{ route('admin.users.index') }}" class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded-lg">
                        Clear
                    </a>
                @endif
            </form>
        </div>

        <!-- Success Message -->
        @if(session('success'))
            <div class="mx-6 mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Users Table -->
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($users as $user)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900">{{ $user->full_name }}</div>
                                    <div class="text-sm text-gray-500">Hired: {{ $user->date_hired?->format('M d, Y') ?? 'N/A' }}</div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">{{ $user->email }}</div>
                                <div class="text-sm text-gray-500">{{ $user->phone_number ?? 'No phone' }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->user_type === 'admin' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800' }}">
                                    {{ $user->getUserTypeLabel() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    {{ $user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                    {{ $user->account_is_set ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $user->account_is_set ? 'Setup Complete' : 'Needs Setup' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex space-x-2">
                                    <a href="{{ route('admin.users.show', $user) }}" 
                                       class="text-blue-600 hover:text-blue-900">View</a>
                                    <a href="{{ route('admin.users.edit', $user) }}" 
                                       class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                    <form method="POST" action="{{ route('admin.users.toggle-status', $user) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-yellow-600 hover:text-yellow-900">
                                            {{ $user->is_active ? 'Deactivate' : 'Activate' }}
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                No users found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        @if($users->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $users->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection