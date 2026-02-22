<x-mail::message>
# Welcome to {{ config('app.name') }}, {{ $userName }}!

We're thrilled to have you on board. **{{ config('app.name') }}** is your go-to place for finding and listing properties — completely free.

Here's what you can do right away:

- **Browse listings** — explore the latest properties available in your area.
- **Post your own property** — list a property in just a few minutes.
- **Stay updated** — get notified when new listings match your interests.

<x-mail::button :url="$browseUrl" color="primary">
Browse Properties
</x-mail::button>

Ready to post your first listing?

<x-mail::button :url="$createUrl" color="success">
Create a Listing
</x-mail::button>

If you have any questions, just reply to this email — we're happy to help.

Welcome aboard,<br>
{{ config('app.name') }}
</x-mail::message>
