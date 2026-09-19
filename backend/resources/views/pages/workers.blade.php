@extends('layouts.app')

@section('title', 'Skilled workers - '.config('branding.name'))

@section('content')
    <section class="dash">
        <div class="container">
            <header class="dash-head">
                <div>
                    <h1>Skilled workers</h1>
                    <p class="dash-lede">
                        Find people who do the work you need. Filter by trade —
                        arrangements and payment happen directly between you.
                    </p>
                </div>
            </header>

            @if ($categories->isNotEmpty())
                <nav class="dash-tag-row" aria-label="Filter by trade" style="margin-bottom: var(--space-3);">
                    <a class="dash-tag {{ $selected ? '' : 'dash-tag--active' }}"
                       href="{{ route('workers') }}">All trades</a>
                    @foreach ($categories as $category)
                        <a class="dash-tag {{ $selected === $category->id ? 'dash-tag--active' : '' }}"
                           href="{{ route('workers', ['category_id' => $category->id]) }}">{{ $category->name }}</a>
                    @endforeach
                </nav>
            @endif

            @if ($workers->isEmpty())
                <div class="dash-card">
                    <div class="dash-empty">
                        <svg width="34" height="34" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M7 10h3v-3l-3.5 -3.5a6 6 0 0 1 8 8l6 6a2 2 0 0 1 -3 3l-6 -6a6 6 0 0 1 -8 -8l3.5 3.5"/></svg>
                        <p>No skilled workers found{{ $selected ? ' for that trade' : '' }} yet.</p>
                    </div>
                </div>
            @else
                <div class="dash-grid">
                    @foreach ($workers as $worker)
                        <div class="dash-card">
                            <div class="dash-card-head">
                                <h2>{{ $worker->user?->name ?? 'Skilled worker' }}</h2>
                                @if ($worker->user?->district)
                                    <span class="small muted">{{ $worker->user->district->name }}</span>
                                @endif
                            </div>

                            @if ($worker->skillCategories->isNotEmpty())
                                <div class="dash-tag-row">
                                    @foreach ($worker->skillCategories as $skill)
                                        <span class="dash-tag">{{ $skill->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if ($worker->services)
                                <p class="muted small" style="margin-top: var(--space-2);">
                                    {{ \Illuminate\Support\Str::limit($worker->services, 180) }}
                                </p>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endsection
