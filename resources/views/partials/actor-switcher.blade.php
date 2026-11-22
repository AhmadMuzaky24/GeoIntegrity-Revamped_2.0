@php($current = \App\Support\Actor::current())
<div style="margin-top: 10px; font-size: 13px;">
    <span style="color: #cbd5e1;">Masuk sebagai:</span>

    @foreach ($actors as $actor)
        @if ($current && $current->id === $actor->id)
            <strong style="color: #fff;">{{ $actor->name }}</strong>
        @else
            <a href="{{ route('actor.switch', $actor->id) }}"
               style="color: #93c5fd; text-decoration: none;">{{ $actor->name }}</a>
        @endif

        @unless ($loop->last)
            <span style="color: #64748b;">|</span>
        @endunless
    @endforeach
</div>
