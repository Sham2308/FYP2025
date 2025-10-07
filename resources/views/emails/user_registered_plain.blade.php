Welcome to TapNBorrow

Hi {{ $user->name }},

Your card has been registered successfully.

UID: {{ $user->uid }}
Student/Staff ID: {{ $user->staff_id ?? '-' }}
@isset($tempPassword)
Temporary Password: {{ $tempPassword }}
@endisset

Open TapNBorrow: {{ config('app.url') }}

For security, please log in and change your password right away (Profile → Change Password).
