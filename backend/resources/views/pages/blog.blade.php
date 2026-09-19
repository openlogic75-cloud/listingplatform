@extends('layouts.app')

@section('title', 'Blog - '.config('branding.name'))

@section('content')
    <section class="dash">
        <div class="container">
            <header class="dash-head">
                <div>
                    <h1>Blog</h1>
                    <p class="dash-lede">
                        Stories about the farms, makers and businesses in the
                        marketplace — who they are and what they sell.
                    </p>
                </div>
            </header>

            @if ($posts->isEmpty())
                <div class="dash-card">
                    <div class="dash-empty">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 5h18v14h-18z"/><path d="M7 9h10"/><path d="M7 13h6"/></svg>
                        <p>No stories published yet.</p>
                    </div>
                </div>
            @else
                <div class="dash-grid">
                    @foreach ($posts as $post)
                        <a class="dash-card" href="{{ route('blog.show', $post) }}"
                           style="text-decoration: none; color: inherit;">
                            @if ($post->cover_image)
                                <img src="{{ Storage::disk('public')->url($post->cover_image) }}"
                                     alt=""
                                     style="width: 100%; height: 160px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: var(--space-2);">
                            @endif
                            <h2 style="font-size: 1.1rem;">{{ $post->title }}</h2>
                            <p class="muted small" style="margin: 4px 0;">
                                {{ $post->published_at?->format('d M Y') }}
                                @if ($post->vendor)
                                    &middot; {{ $post->vendor->display_name }}
                                @endif
                            </p>
                            <p class="muted small">{{ $post->displayExcerpt() }}</p>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
