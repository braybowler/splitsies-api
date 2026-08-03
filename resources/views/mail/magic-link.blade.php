<x-mail::message>
# Log in to Splitsies

Click the button below to log in. This link is single-use and expires in {{ $ttlMinutes }} minutes.

<x-mail::button :url="$url">
Log in to Splitsies
</x-mail::button>

If you didn't request this link, you can safely ignore this email — no one can access your account without it.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
