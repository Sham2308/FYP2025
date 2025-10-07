<?php

namespace App\Http\Controllers;

use App\Mail\UserRegisteredMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class NfcRegisterController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'uid'      => ['required','string','max:191'],
            'name'     => ['required','string','max:255'],
            'email'    => ['required','email','max:255', Rule::unique('users','email')],
            'staff_id' => ['nullable','string','max:191'],
        ]);

        // Generate a temporary password (12 chars)
        $tempPassword = Str::random(12);

        // Your User model has 'password' => 'hashed', so no need to bcrypt() here
        $user = User::create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'uid'      => $data['uid'],
            'staff_id' => $data['staff_id'] ?? null,
            'password' => $tempPassword, // auto-hashed by cast
        ]);

        try {
            // Pass the temp password into the email so the user can log in
            Mail::to($user->email)->send(new UserRegisteredMail($user, $tempPassword));
        } catch (\Throwable $e) {
            Log::error('Register email failed: '.$e->getMessage());
        }

        return back()->with('success', 'Saved and email notification sent.');
    }
}
