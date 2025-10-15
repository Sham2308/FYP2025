<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Google\Client as GoogleClient;
use Google\Service\Sheets as GoogleSheets;

class PublicRegisterController extends Controller
{
    /**
     * Show the registration form for public users (students/staff).
     */
    public function showForm()
    {
        return view('public_register');
    }

    /**
     * Handle form submission:
     * - Create or update user
     * - Send confirmation email via SMTP2GO
     * - (Optional) Sync to Google Sheet
     * - Redirect to Borrow page
     */
    public function store(Request $request)
    {
        Log::info('🟢 PublicRegisterController@store called');

        $validated = $request->validate([
            'uid'                 => ['required', 'string', 'max:255'],
            'name'                => ['required', 'string', 'max:255'],
            'email'               => ['required', 'email'],
            'student_or_staff_id' => ['required', 'string', 'max:255'],
        ]);

        // Normalize email for reliability
        $email = strtolower(trim($validated['email']));

        Log::info("🟢 Validation passed for: {$email}");

        // Create or update user record
        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'uid'      => $validated['uid'],
                'name'     => $validated['name'],
                'staff_id' => $validated['student_or_staff_id'],
                'role'     => 'user',
                'password' => Hash::make('temporary'),
            ]
        );

        Log::info("🟢 User created or updated: {$email}");

        // Send confirmation email
        try {
            Mail::raw(
                "Hello {$user->name},\n\nYour UID ({$user->uid}) has been successfully registered in TapNBorrow. You can now scan your card to borrow items.\n\nThank you,\nTapNBorrow Team",
                function ($m) use ($email) {
                    $m->from('noreply@tapnborrow.com', 'TapNBorrow');
                    $m->to($email);
                    $m->subject('TapNBorrow Registration Successful');
                }
            );
            Log::info("✅ Registration email sent to {$email}");
        } catch (\Throwable $e) {
            Log::error('❌ Email send failed: '.$e->getMessage());
        }

        // Optional: Google Sheets sync
        if (env('USE_GOOGLE_SHEETS_API', false)) {
            try {
                $client = new GoogleClient();
                $client->setApplicationName('TapNBorrow UID Sync');
                $client->setScopes([GoogleSheets::SPREADSHEETS]);
                $client->setAuthConfig($this->resolveGoogleCredsPath());
                $client->setAccessType('offline');

                $service = new GoogleSheets($client);
                $spreadsheetId = env('GOOGLE_SHEET_ID');

                if ($spreadsheetId) {
                    $range  = 'Users!A:C';
                    $values = [[$user->uid, $user->staff_id, $user->name]];
                    $body   = new \Google\Service\Sheets\ValueRange(['values' => $values]);
                    $params = ['valueInputOption' => 'RAW'];
                    $service->spreadsheets_values->append($spreadsheetId, $range, $body, $params);
                    Log::info('✅ Synced to Google Sheet');
                } else {
                    Log::warning('⚠️ GOOGLE_SHEET_ID not set; skipping Google Sheets sync.');
                }
            } catch (\Throwable $e) {
                Log::error('❌ Google Sheets sync failed: '.$e->getMessage());
            }
        }

        // Redirect directly to borrow page (no logout needed)
        return redirect()->route('borrow.index')->with('success', 'Registration complete! Please check your email.');
    }

    /**
     * Helper: locate Google credentials JSON path.
     */
    private function resolveGoogleCredsPath(): string
    {
        $envPath = env('GOOGLE_SHEETS_CREDENTIALS_PATH');
        if (!$envPath || trim($envPath) === '') {
            return storage_path('app/google/credentials/credentials.json');
        }
        $base = base_path($envPath);
        return is_dir($base)
            ? rtrim($base, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.'credentials.json'
            : $base;
    }
}
