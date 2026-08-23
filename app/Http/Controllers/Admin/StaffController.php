<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class StaffController extends Controller
{
    public function index()
    {
        // Show internal staff (admin/warehouse_staff) plus role='none' accounts that
        // are NOT vendors — i.e. accounts that could still be promoted to staff.
        // Vendors also carry role='none', so excluding anyone with a Vendor row
        // keeps them off this page entirely.
        $staff = User::whereIn('role', ['admin', 'warehouse_staff'])
            ->orWhere(function ($query) {
                $query->where('role', 'none')->whereDoesntHave('vendor');
            })
            ->get();

        return view('admin.staff.index', compact('staff'));
    }

    /**
     * Show the form to create a new internal staff account.
     * Distinct from public vendor registration — only reachable by an admin.
     */
    public function create()
    {
        return view('admin.staff.create');
    }

    /**
     * Create a new admin or warehouse_staff account directly.
     * No Vendor row is created — this user is internal staff, not a vendor.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'role' => ['required', 'in:admin,warehouse_staff'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => $validated['role'],
        ]);

        return redirect()->route('admin.staff.index')->with('success', "{$validated['name']} added as " . str_replace('_', ' ', $validated['role']) . '.');
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'role' => 'required|in:admin,warehouse_staff,none',
        ]);

        // Safety: prevent the last admin from demoting themselves accidentally
        if ($user->id === auth()->id() && $validated['role'] !== 'admin' && User::where('role', 'admin')->count() <= 1) {
            return back()->with('error', 'You cannot remove the last remaining admin account.');
        }

        $user->update(['role' => $validated['role']]);

        return back()->with('success', "{$user->name}'s role updated to " . str_replace('_', ' ', $validated['role']) . ".");
    }
}
