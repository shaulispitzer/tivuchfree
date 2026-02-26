@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (in_array(trim($slot), ['Laravel', config('app.name')], true))
<img src="{{ url('tivuch-free-logo.png') }}" class="logo" alt="{{ config('app.name') }} logo">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
