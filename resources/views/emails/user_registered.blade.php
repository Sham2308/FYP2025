@component('mail::message')
# 🎉 Welcome to TapNBorrow

Hi {{ $user->name }},

Your card has been successfully registered in **TapNBorrow**.

@component('mail::panel')
- **UID:** {{ $user->uid }}
- **Student/Staff ID:** {{ $user->staff_id ?? '—' }}
@isset($tempPassword)
- **Temporary Password:** **{{ $tempPassword }}**
@endisset
@endcomponent

@component('mail::button', ['url' => config('app.url')])
Open TapNBorrow
@endcomponent

> 🔒 For security reasons, please log in and change your password immediately (Profile → Change Password).

Thanks,<br>
**{{ config('app.name') }} Team**
@endcomponent
