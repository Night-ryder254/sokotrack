<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     * Public registration is vendor-only — staff accounts are created
     * separately by an admin from the Staff panel, never self-service.
     */
    public function create(): View
    {
        return view('auth.login', ['initialTab' => 'register']);
    }

    /**
     * Handle an incoming vendor registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'business_name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
        ]);

        [$user, $vendor] = DB::transaction(function () use ($request) {
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'none', // vendors are external — role stays 'none', vendor status is signaled by the Vendor row itself
            ]);

            $vendor = Vendor::create([
                'user_id' => $user->id,
                'vendor_tier_id' => null,
                'business_name' => $request->business_name,
                'phone' => $request->phone,
                'credit_limit' => 0,
                'outstanding_balance' => 0,
                'loyalty_points' => 0,
            ]);

            return [$user, $vendor];
        });

        event(new Registered($user));

        Auth::login($user);

        Mail::to($user->email)->send(new \App\Mail\VendorWelcome($vendor));

        return redirect(route('dashboard', absolute: false));
    }
}
