@extends('layouts.admin')

@section('title', 'Volunteers')
@section('heading', 'Volunteers')
@section('lede', 'Volunteers register as pending. Approve them to let them sign in and take site visits; rejecting keeps them out.')

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Awaiting approval</h2>
                    <p>{{ $pending->count() }} {{ \Illuminate\Support\Str::plural('application', $pending->count()) }} pending.</p>
                </div>
            </header>

            @forelse ($pending as $volunteer)
                <div class="inline-form" style="justify-content: space-between; width: 100%; padding: var(--adm-space-3) 0; border-top: 1px solid var(--adm-border);">
                    <div>
                        <strong>{{ $volunteer->user?->name ?? 'Unknown' }}</strong>
                        <p class="muted small" style="margin: 0;">
                            {{ $volunteer->user?->email }}
                            &middot; registered {{ $volunteer->created_at?->format('j M Y, H:i') }}
                            @if ($volunteer->availability)
                                &middot; availability: {{ $volunteer->availability }}
                            @endif
                        </p>
                    </div>
                    <div class="inline-form">
                        <form method="post" action="{{ route('admin.volunteers.approve', $volunteer) }}">
                            @csrf
                            <button class="button" type="submit">Approve</button>
                        </form>
                        <form method="post" action="{{ route('admin.volunteers.reject', $volunteer) }}">
                            @csrf
                            <button class="button secondary" type="submit">Reject</button>
                        </form>
                    </div>
                </div>
            @empty
                <p class="muted">No volunteer applications are waiting.</p>
            @endforelse
        </section>

        <section class="card">
            <header>
                <div>
                    <h2>Recently reviewed</h2>
                </div>
            </header>

            @if ($reviewed->isEmpty())
                <p class="muted">Nothing reviewed yet.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Volunteer</th>
                            <th scope="col">Status</th>
                            <th scope="col">Reviewed</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reviewed as $volunteer)
                            <tr>
                                <td>
                                    {{ $volunteer->user?->name ?? 'Unknown' }}
                                    <span class="small muted" style="display: block;">{{ $volunteer->user?->email }}</span>
                                </td>
                                <td>
                                    @if ($volunteer->verification_status === 'approved')
                                        <span class="status ok">Approved</span>
                                    @else
                                        <span class="status bad">Rejected</span>
                                    @endif
                                </td>
                                <td class="small muted">
                                    {{ $volunteer->reviewed_at?->format('j M Y, H:i') ?? '—' }}
                                    @if ($volunteer->reviewedBy)
                                        by {{ $volunteer->reviewedBy->name }}
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
