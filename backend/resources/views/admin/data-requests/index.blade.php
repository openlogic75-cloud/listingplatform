@extends('layouts.admin')

@section('title', 'Data requests')
@section('heading', 'Data requests')
@section('lede', 'Every export and deletion request, with its outcome. The ledger keeps the user id and the outcome only - it holds no personal data, so it survives the deletion it records.')

@section('content')
    @if ($requests->isEmpty())
        <section class="card">
            <h2>No requests yet</h2>
            <p class="muted">
                Registered users can export or delete their data themselves from the app
                profile screen. Requests appear here as soon as one is made.
            </p>
        </section>
    @else
        <div class="stack">
            @foreach ($requests as $dataRequest)
                <section class="card">
                    <header>
                        <div>
                            <h2>
                                {{ ucfirst($dataRequest->type) }} request
                                <span class="small muted">#{{ $dataRequest->id }}</span>
                            </h2>
                            <p class="small">
                                Requested
                                {{ $dataRequest->requested_at?->format('d M Y, H:i') ?? 'unknown' }}
                                @if ($dataRequest->processed_at)
                                    · handled
                                    {{ $dataRequest->processed_at->format('d M Y, H:i') }}
                                @endif
                            </p>
                        </div>
                        <span class="status {{ $dataRequest->status === 'completed' ? 'ok' : ($dataRequest->status === 'rejected' ? 'bad' : '') }}">
                            {{ ucfirst($dataRequest->status) }}
                        </span>
                    </header>

                    <p class="small">
                        Account:
                        <strong>{{ $dataRequest->user?->name ?? 'Account no longer exists' }}</strong>
                        @if ($dataRequest->user)
                            <span class="muted">
                                (id {{ $dataRequest->user_id }}, {{ $dataRequest->user->role }})
                            </span>
                        @endif
                    </p>

                    @if ($dataRequest->notes)
                        <p class="small muted">
                            Recorded outcome: {{ $dataRequest->notes }}
                        </p>
                    @endif

                    @if ($dataRequest->status !== 'completed')
                        <div class="inline-form" style="margin-top: var(--adm-space-3);">
                            <form method="post"
                                  action="{{ route('admin.data-requests.process', $dataRequest) }}">
                                @csrf
                                <div class="field" style="margin: 0;">
                                    <label for="process-notes-{{ $dataRequest->id }}">
                                        Re-run this request
                                    </label>
                                    <input id="process-notes-{{ $dataRequest->id }}"
                                           name="notes" type="text" maxlength="500"
                                           placeholder="Optional note for the ledger">
                                </div>
                                <button class="button" type="submit">Re-run</button>
                            </form>

                            <form method="post"
                                  action="{{ route('admin.data-requests.reject', $dataRequest) }}">
                                @csrf
                                <div class="field" style="margin: 0;">
                                    <label for="reject-notes-{{ $dataRequest->id }}">
                                        Rejection reason (required)
                                    </label>
                                    <input id="reject-notes-{{ $dataRequest->id }}"
                                           name="notes" type="text" required maxlength="500"
                                           placeholder="Why this request cannot be honoured">
                                </div>
                                <button class="button secondary" type="submit">Reject</button>
                            </form>
                        </div>
                    @endif
                </section>
            @endforeach

            <div class="pagination">
                {{ $requests->links() }}
            </div>
        </div>
    @endif
@endsection