@extends('layouts.app')

@section('title', $post->title.' - '.config('branding.name'))

@section('content')
    <section class="dash">
        <div class="container" style="max-width: 760px;">
            <p class="breadcrumb"><a href="{{ route('blog') }}">Blog</a></p>

            <article class="dash-card">
                @if ($post->cover_image)
                    <img src="{{ Storage::disk('public')->url($post->cover_image) }}"
                         alt=""
                         style="width: 100%; max-height: 360px; object-fit: cover; border-radius: var(--radius-md); margin-bottom: var(--space-3);">
                @endif

                <h1>{{ $post->title }}</h1>
                <p class="muted small">
                    {{ $post->published_at?->format('d M Y') }}
                    @if ($post->author)
                        &middot; {{ $post->author->name }}
                    @endif
                    @if ($post->vendor)
                        &middot; featuring {{ $post->vendor->display_name }}
                    @endif
                </p>

                {{-- Plain text, escaped by Blade, line breaks preserved. --}}
                <div style="white-space: pre-line; margin-top: var(--space-3); font-size: 1.02rem;">
                    {{ $post->body }}
                </div>

                @if (collect($post->images ?? [])->isNotEmpty())
                    <h2 style="font-size: 1.1rem; margin-top: var(--space-4);">Photos from the visit</h2>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(180px, 1fr)); gap: var(--space-2);">
                        @foreach ($post->images as $path)
                            <a href="{{ Storage::disk('public')->url($path) }}" target="_blank" rel="noopener">
                                <img src="{{ Storage::disk('public')->url($path) }}" alt="Site visit photo"
                                     style="width: 100%; height: 150px; object-fit: cover; border-radius: var(--radius-md); border: 1px solid var(--color-border);">
                            </a>
                        @endforeach
                    </div>
                @endif

                @if ($post->vendor)
                    <p style="margin-top: var(--space-4);">
                        <a class="btn btn-primary" href="{{ route('vendor.show', $post->vendor) }}">
                            Visit {{ $post->vendor->display_name }}
                        </a>
                    </p>
                @endif
            </article>
        </div>
    </section>
@endsection
