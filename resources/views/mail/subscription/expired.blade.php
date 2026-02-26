<x-mail::message>
# Subscription Expired

Your 30-day property subscription has ended. You will no longer receive emails about new properties.

If you would like to continue receiving updates, you can subscribe again anytime.

<x-mail::button :url="$subscribeUrl">
Subscribe again
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
