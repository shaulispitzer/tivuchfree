<x-mail::message>
# Verify your email

Enter the following code to complete your property subscription:

**Your code:** {{ $otp }}

This code expires in 10 minutes.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
