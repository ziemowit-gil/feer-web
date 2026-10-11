{{-- <link rel="alternate"> do kanału RSS bloga — stos `structured_data` leży w <head> layoutu. --}}
@push('structured_data')
    <link rel="alternate" type="application/rss+xml" title="Wiem FEER — kanał RSS" href="{{ route('blog.feed') }}">
@endpush
