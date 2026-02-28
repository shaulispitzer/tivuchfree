<x-mail::message>
# {{ __('mail.welcome.title', ['app' => config('app.name'), 'name' => $userName]) }}

{{ __('mail.welcome.intro', ['app' => config('app.name')]) }}

{{ __('mail.welcome.what_you_can_do') }}

- {!! __('mail.welcome.browse') !!}
- {!! __('mail.welcome.post') !!}
- {!! __('mail.welcome.stay_updated') !!}

<x-mail::button :url="$browseUrl" color="primary">
{{ __('mail.welcome.browse_button') }}
</x-mail::button>

{{ __('mail.welcome.ready_to_post') }}

<x-mail::button :url="$createUrl" color="success">
{{ __('mail.welcome.create_button') }}
</x-mail::button>

{{ __('mail.welcome.questions') }}

{{ __('mail.welcome.signoff') }}<br>
{{ config('app.name') }}
</x-mail::message>
