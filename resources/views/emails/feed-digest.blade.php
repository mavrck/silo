<x-mail::message>
# {{ $feed->title }}

Hi {{ $user->name }}, you have **{{ $totalCount }}** unread {{ Str::plural('entry', $totalCount) }}
since your last {{ $frequency }} digest.

@foreach ($entries as $entry)
- [{{ $entry->title ?: 'Untitled' }}]({{ url('/entries/'.$entry->id) }}){!! $entry->summary ? '<br><span style="color:#71717a;font-size:13px;">'.e(Str::limit($entry->summary, 200)).'</span>' : '' !!}
@endforeach

@if ($displayedCount < $totalCount)
_...and {{ $totalCount - $displayedCount }} more in the app._
@endif

<x-mail::button :url="route('feeds.show', $feed)">
Open in Silo
</x-mail::button>

You're receiving this because "{{ $feed->title }}" is set to send a **{{ $frequency }}** digest. Change this anytime from your
[feeds page]({{ url('/feeds') }}).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
