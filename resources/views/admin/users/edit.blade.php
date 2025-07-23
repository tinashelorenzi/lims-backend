@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto">
        <div class="bg-white rounded-lg shadow-lg">
            <!-- Header -->
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">Edit User</h1>
                        <p class="text-gray-600">Update {{ $user->full_name }}'s information</p>
                    </div>
                    <a href="{{ route('admin.users.index') }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg">
                        Back to Users
                    </a>
                </div>
            </div>

            <!-- Form -->
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="px-6 py-4">
                @csrf
                @method('PUT')

                <!-- Personal Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Personal Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- First Name -->
                        <div>
                            <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">
                                First Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="first_name" 
                                   name="first_name" 
                                   value="{{ old('first_name', $user->first_name) }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('first_name') border-red-500 @enderror">
                            @error('first_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Last Name -->
                        <div>
                            <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">
                                Last Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   id="last_name" 
                                   name="last_name" 
                                   value="{{ old('last_name', $user->last_name) }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('last_name') border-red-500 @enderror">
                            @error('last_name')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Contact Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Email -->
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 mb-1">
                                Email Address <span class="text-red-500">*</span>
                            </label>
                            <input type="email" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $user->email) }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror">
                            @error('email')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Phone Number -->
                        <div>
                            <label for="phone_number" class="block text-sm font-medium text-gray-700 mb-1">
                                Phone Number
                            </label>
                            <input type="tel" 
                                   id="phone_number" 
                                   name="phone_number" 
                                   value="{{ old('phone_number', $user->phone_number) }}"
                                   placeholder="e.g., +27 11 123 4567"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('phone_number') border-red-500 @enderror">
                            @error('phone_number')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Employment Information -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Employment Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Date Hired -->
                        <div>
                            <label for="date_hired" class="block text-sm font-medium text-gray-700 mb-1">
                                Date Hired <span class="text-red-500">*</span>
                            </label>
                            <input type="date" 
                                   id="date_hired" 
                                   name="date_hired" 
                                   value="{{ old('date_hired', $user->date_hired?->format('Y-m-d')) }}"
                                   max="{{ date('Y-m-d') }}"
                                   required
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('date_hired') border-red-500 @enderror">
                            @error('date_hired')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- User Type -->
                        <div>
                            <label for="user_type" class="block text-sm font-medium text-gray-700 mb-1">
                                User Type <span class="text-red-500">*</span>
                            </label>
                            <select id="user_type" 
                                    name="user_type" 
                                    required
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('user_type') border-red-500 @enderror">
                                <option value="">Select User Type</option>
                                @foreach(\App\Models\User::USER_TYPES as $key => $label)
                                    <option value="{{ $key }}" {{ old('user_type', $user->user_type) === $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_type')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                <!-- Account Status -->
                <div class="mb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Account Status</h3>
                    
                    <div class="space-y-4">
                        <!-- Active Status -->
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   id="is_active" 
                                   name="is_active" 
                                   value="1"
                                   {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                                   class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                            <label for="is_active" class="ml-2 block text-sm text-gray-900">
                                Active Account
                            </label>
                        </div>

                        <!-- Account Info -->
                        <div class="p-4 bg-gray-50 rounded-lg">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="font-medium text-gray-700">Account Setup:</span>
                                    <span class="ml-2 inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        {{ $user->account_is_set ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                        {{ $user->account_is_set ? 'Complete' : 'Needs Setup' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Last Login:</span>
                                    <span class="ml-2 text-gray-600">
                                        {{ $user->last_login_at?->format('M d, Y H:i') ?? 'Never' }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Created:</span>
                                    <span class="ml-2 text-gray-600">
                                        {{ $user->created_at->format('M d, Y H:i') }}
                                    </span>
                                </div>
                                <div>
                                    <span class="font-medium text-gray-700">Updated:</span>
                                    <span class="ml-2 text-gray-600">
                                        {{ $user->updated_at->format('M d, Y H:i') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="flex justify-end space-x-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('admin.users.index') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                        Cancel
                    </a>
                    <button type="submit" 
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:ring-2 focus:ring-blue-500">
                        Update User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection