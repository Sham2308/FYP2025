<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;
use Illuminate\Support\Str;   // 👈 add this

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'uid'      => ['required', 'string', 'max:255', 'unique:users,uid'],
            'name'     => ['required', 'string', 'max:255'],
            'staff_id' => ['nullable', 'string', 'max:255'],
            // email now optional
            'email'    => ['nullable', 'string', 'email', 'max:255'],

        ]);

        $user = User::create([
            'uid'      => $request->uid,
            'name'     => $request->name,
            'staff_id' => $request->staff_id,
            'email'    => $request->email, // can be null
            // generate random password automatically
            'password' => Hash::make(Str::random(16)),
        ]);

        event(new Registered($user));
        Auth::login($user);

        return redirect(route('dashboard', absolute: false));
    }
}
