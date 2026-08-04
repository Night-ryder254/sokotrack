<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class StaffController extends Controller
{
    public function index()
    {
        $staff = User::whereIn('role', ['admin', 'warehouse_staff'])->orWhere('role', 'none')->get();
        return view('admin.staff.index', compact('staff'));
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
