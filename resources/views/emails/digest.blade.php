<x-mail::message>
# Your {{ ucfirst($frequency) }} Digest

Hi {{ $user->name }}, you have **{{ $totalCount }}** unread {{ Str::plural('entry', $totalCount) }}
from {{ $frequency === 'weekly' ? 'the last week' : 'the last day' }}.

@foreach ($groupedEntries as $categoryName => $feeds)
## {{ $categoryName }}

@foreach ($feeds as $feedName => $entries)
**{{ $feedName }}**

@foreach ($entries as $entry)
- [{{ $entry->title ?: 'Untitled' }}]({{ url('/entries/'.$entry->id) }})
@endforeach

@endforeach
@endforeach

@if ($displayedCount < $totalCount)
_...and {{ $totalCount - $displayedCount }} more in the app._
@endif

<x-mail::button :url="url('/entries?unread=1')">
Open Silo
</x-mail::button>

You're receiving this because your digest is set to **{{ $frequency }}**. Change this anytime in your
[profile settings]({{ url('/profile') }}).

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
