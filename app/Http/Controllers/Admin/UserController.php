<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Display a listing of users.
     */
    public function index(Request $request)
    {
        $query = User::query();

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                  ->orWhere('last_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filter by user type
        if ($request->filled('user_type')) {
            $query->where('user_type', $request->user_type);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone_number' => 'nullable|string|max:20',
            'date_hired' => 'required|date',
            'user_type' => ['required', Rule::in(array_keys(User::USER_TYPES))],
        ]);

        // Generate a temporary password
        $tempPassword = Str::random(8);

        $user = User::create([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => Hash::make($tempPassword),
            'date_hired' => $request->date_hired,
            'user_type' => $request->user_type,
            'account_is_set' => false,
            'is_active' => true,
        ]);

        // Store temp password in session to show to admin (in real app, email this)
        session()->flash('temp_password', $tempPassword);
        session()->flash('new_user_email', $user->email);

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully! Temporary password: ' . $tempPassword);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user)
    {
        return view('admin.users.show', compact('user'));
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone_number' => 'nullable|string|max:20',
            'date_hired' => 'required|date',
            'user_type' => ['required', Rule::in(array_keys(User::USER_TYPES))],
            'is_active' => 'boolean',
        ]);

        $user->update([
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'date_hired' => $request->date_hired,
            'user_type' => $request->user_type,
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        // Don't allow deletion of the last admin
        if ($user->isAdmin() && User::where('user_type', 'admin')->count() <= 1) {
            return redirect()->route('admin.users.index')
                ->with('error', 'Cannot delete the last administrator.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Reset user password
     */
    public function resetPassword(User $user)
    {
        $tempPassword = Str::random(8);
        
        $user->update([
            'password' => Hash::make($tempPassword),
            'account_is_set' => false,
        ]);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'Password reset successfully! New temporary password: ' . $tempPassword);
    }

    /**
     * Toggle user status
     */
    public function toggleStatus(User $user)
    {
        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        
        return redirect()->route('admin.users.index')
            ->with('success', "User {$status} successfully!");
    }
}