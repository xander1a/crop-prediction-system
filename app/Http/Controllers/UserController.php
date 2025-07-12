<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
    {
        $users = User::latest()->paginate(15);
        return view('users', compact('users'));
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:farmer,trader,policymaker,admin',
            'language_preference' => 'required|string|max:10',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        }

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'language_preference' => $request->language_preference,
                'password' => Hash::make($request->password),
            ]);

            return redirect()->route('users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating user: ' . $e->getMessage());
        }
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'phone' => 'nullable|string|max:20',
            'role' => 'required|in:farmer,trader,policymaker,admin',
            'language_preference' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput()
                ->with('error', 'Please fix the validation errors.');
        }

        try {
            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'role' => $request->role,
                'language_preference' => $request->language_preference,
            ]);

            return redirect()->route('users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deletion of the currently authenticated user
            if (auth()->id() === $user->id) {
                return redirect()->route('users.index')
                    ->with('error', 'You cannot delete your own account.');
            }

            $user->delete();

            return redirect()->route('users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error deleting user: ' . $e->getMessage());
        }
    }

    /**
     * Update user role
     */
    public function updateRole(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'role' => 'required|in:farmer,trader,policymaker,admin',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->with('error', 'Invalid role selected.');
        }

        try {
            // Prevent changing own role to non-admin if current user is admin
            if (auth()->id() === $user->id && auth()->user()->role === 'admin' && $request->role !== 'admin') {
                return redirect()->route('users.index')
                    ->with('error', 'You cannot change your own admin role.');
            }

            $user->update([
                'role' => $request->role,
            ]);

            return redirect()->route('users.index')
                ->with('success', 'User role updated successfully.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating user role: ' . $e->getMessage());
        }
    }

    /**
     * Search users
     */
    public function search(Request $request)
    {
        $search = $request->get('search');
        $role = $request->get('role');

        $query = User::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', '%' . $search . '%')
                  ->orWhere('email', 'like', '%' . $search . '%')
                  ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if ($role && $role !== 'all') {
            $query->where('role', $role);
        }

        $users = $query->latest()->paginate(15);

        return view('users.index', compact('users'));
    }

    /**
     * Export users to CSV
     */
    public function export()
    {
        try {
            $users = User::all();
            $filename = 'users_' . date('Y-m-d_H-i-s') . '.csv';

            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ];

            $callback = function () use ($users) {
                $file = fopen('php://output', 'w');
                fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'Role', 'Language Preference', 'Created At']);

                foreach ($users as $user) {
                    fputcsv($file, [
                        $user->id,
                        $user->name,
                        $user->email,
                        $user->phone,
                        $user->role,
                        $user->language_preference,
                        $user->created_at->format('Y-m-d H:i:s'),
                    ]);
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error exporting users: ' . $e->getMessage());
        }
    }

    /**
     * Toggle user status (if you have an active/inactive status)
     */
    public function toggleStatus(User $user)
    {
        try {
            // This assumes you have an 'is_active' column in your users table
            // If not, you can add it with: $table->boolean('is_active')->default(true);
            $user->update([
                'is_active' => !$user->is_active,
            ]);

            $status = $user->is_active ? 'activated' : 'deactivated';

            return redirect()->route('users.index')
                ->with('success', "User {$status} successfully.");
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating user status: ' . $e->getMessage());
        }
    }

    /**
     * Bulk operations
     */
    public function bulkAction(Request $request)
    {
        $action = $request->get('action');
        $userIds = $request->get('user_ids', []);

        if (empty($userIds)) {
            return redirect()->back()
                ->with('error', 'Please select at least one user.');
        }

        try {
            switch ($action) {
                case 'delete':
                    // Prevent deletion of current user
                    $userIds = array_diff($userIds, [auth()->id()]);
                    User::whereIn('id', $userIds)->delete();
                    return redirect()->route('users.index')
                        ->with('success', 'Selected users deleted successfully.');

                case 'activate':
                    User::whereIn('id', $userIds)->update(['is_active' => true]);
                    return redirect()->route('users.index')
                        ->with('success', 'Selected users activated successfully.');

                case 'deactivate':
                    // Prevent deactivation of current user
                    $userIds = array_diff($userIds, [auth()->id()]);
                    User::whereIn('id', $userIds)->update(['is_active' => false]);
                    return redirect()->route('users.index')
                        ->with('success', 'Selected users deactivated successfully.');

                default:
                    return redirect()->back()
                        ->with('error', 'Invalid action selected.');
            }
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error performing bulk action: ' . $e->getMessage());
        }
    }
}