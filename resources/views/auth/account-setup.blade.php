@extends('layouts.app')

@section('title', 'Account Setup')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <!-- Header -->
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Account Setup Required</h2>
                <p class="mt-2 text-gray-600">
                    Welcome to Dr Lab LIMS, {{ auth()->user()->full_name }}!
                </p>
                <p class="mt-1 text-sm text-gray-500">
                    Please set your new password to complete your account setup.
                </p>
            </div>

            <!-- Important Notice -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"></path>
                        </svg>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">Security Notice</h3>
                        <div class="mt-1 text-sm text-blue-700">
                            <p>Your administrator has created this account with a temporary password. You must change it to continue using the system.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Setup Form -->
            <form method="POST" action="{{ route('account.setup') }}" class="space-y-6">
                @csrf

                <!-- Current Password -->
                <div>
                    <label for="current_password" class="block text-sm font-medium text-gray-700">
                        Current Temporary Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="current_password" 
                           name="current_password" 
                           required
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('current_password') border-red-500 @enderror">
                    @error('current_password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Enter the temporary password provided by your administrator.
                    </p>
                </div>

                <!-- New Password -->
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700">
                        New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password" 
                           name="password" 
                           required
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('password') border-red-500 @enderror">
                    @error('password')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-gray-700">
                        Confirm New Password <span class="text-red-500">*</span>
                    </label>
                    <input type="password" 
                           id="password_confirmation" 
                           name="password_confirmation" 
                           required
                           class="mt-1 w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Password Requirements -->
                <div class="p-4 bg-gray-50 rounded-lg">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Password Requirements:</h4>
                    <ul class="text-sm text-gray-600 space-y-1">
                        <li>• At least 8 characters long</li>
                        <li>• Mix of uppercase and lowercase letters</li>
                        <li>• At least one number</li>
                        <li>• At least one special character</li>
                    </ul>
                </div>

                <!-- Submit Button -->
                <div>
                    <button type="submit" 
                            class="w-full flex justify-center py-2 px-4 border border-transparent rounded-lg shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        Complete Account Setup
                    </button>
                </div>
            </form>

            <!-- Account Information -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <div class="text-center">
                    <h4 class="text-sm font-medium text-gray-900 mb-2">Your Account Information</h4>
                    <div class="text-sm text-gray-600 space-y-1">
                        <p><strong>Name:</strong> {{ auth()->user()->full_name }}</p>
                        <p><strong>Email:</strong> {{ auth()->user()->email }}</p>
                        <p><strong>Role:</strong> {{ auth()->user()->getUserTypeLabel() }}</p>
                        <p><strong>Hire Date:</strong> {{ auth()->user()->date_hired?->format('M d, Y') ?? 'N/A' }}</p>
                    </div>
                </div>
            </div>

            <!-- Logout Option -->
            <div class="mt-6 text-center">
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-gray-500 hover:text-gray-700 underline">
                        Sign out instead
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection