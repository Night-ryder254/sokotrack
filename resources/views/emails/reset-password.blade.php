<x-mail::message>
# Reset Your Password

Hi {{ $name }},

We received a request to reset your SokoTrack password. Click below to choose a new one.

<x-mail::button :url="$url">
Reset Password
</x-mail::button>

This link expires in 60 minutes. If you didn't request a password reset, you can safely ignore this email — your password won't be changed.

{{ config('app.name') }}
</x-mail::message>
