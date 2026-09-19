@extends('layouts.admin')

@section('title', 'Verification reviews')
@section('heading', 'Verification reviews')
@section('lede', 'Submitted site-visit reports. Approving issues the verified badge with the volunteer name and fee snapshots; rejecting sends the report back to the volunteer.')

@section('content')
    <div class="stack">
        @forelse ($verifications as $verification)
            @php
                $subject = $verification->subject;
                $subjectLabel = $subject?->display_name ?? $subject?->title ?? '—';
                $checklist = $verification->checklist ?? [];
                $evidence = $verification->evidence ?? [];
            @endphp
            <section class="card">
                <header>
                    <div>
                        <h2>{{ class_basename($verification->subject_type) }} — {{ $subjectLabel }}</h2>
                        <p>
                            Reported by {{ $verification->volunteer?->user?->name ?? 'Unknown volunteer' }}
                            ·
                            submitted {{ $verification->created_at?->format('j M Y, H:i') }}
                        </p>
                    </div>
                    <span class="status">Submitted</span>
                </header>

                <p>{{ $verification->notes }}</p>

                @if (is_array($checklist) && count($checklist) > 0)
                    <h3 class="small muted">Checklist</h3>
                    <ul>
                        @foreach ($checklist as $key => $value)
                            <li>
                                <strong>{{ is_string($key) ? $key : 'Item' }}:</strong>
                                {{ is_bool($value) ? ($value ? 'yes' : 'no') : $value }}
                            </li>
                        @endforeach
                    </ul>
                @endif

                @if (count($evidence) > 0)
                    <h3 class="small muted">Evidence photos</h3>
                    <p style="display: flex; flex-wrap: wrap; gap: var(--adm-space-2);">
                        @foreach ($evidence as $path)
                            <a href="{{ Storage::disk('public')->url($path) }}" target="_blank" rel="noopener">
                                <img class="thumb" src="{{ Storage::disk('public')->url($path) }}"
                                     alt="Site-visit evidence photo" loading="lazy">
                            </a>
                        @endforeach
                    </p>
                @else
                    <p class="muted small" style="margin: 0;">No evidence photos attached.</p>
                @endif

                <div style="display: flex; gap: var(--adm-space-2); margin-top: var(--adm-space-4);">
                    <form method="post"
                          action="{{ route('admin.verifications.approve', $verification) }}">
                        @csrf
                        <button class="button" type="submit">Approve &amp; issue badge</button>
                    </form>
                    <form method="post"
                          action="{{ route('admin.verifications.reject', $verification) }}">
                        @csrf
                        <button class="button secondary" type="submit">Reject</button>
                    </form>
                </div>
            </section>
        @empty
            <section class="card">
                <h2>No reports awaiting review</h2>
                <p class="muted">
                    Submitted site-visit reports appear here. Volunteers file
                    reports from the app once they have visited a site.
                </p>
            </section>
        @endforelse
    </div>
@endsection
