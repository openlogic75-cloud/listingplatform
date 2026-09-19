@extends('layouts.admin')

@section('title', 'Collectors')
@section('heading', 'Collectors')
@section('lede', 'Collectors gather farm produce from their sub-division and bring it to a hub district. Sign one collector to each sub-division — a collector cannot sign in until they are assigned.')

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Signed collectors</h2>
                    <p>{{ $collectors->whereNotNull('collectorAssignment')->count() }} of {{ $collectors->count() }} collectors assigned · hubs: {{ $hubs->pluck('name')->join(', ') ?: 'none set' }}</p>
                </div>
            </header>

            @if ($collectors->isEmpty())
                <p class="muted">No collector accounts yet. They register from the app or the website.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Collector</th>
                            <th scope="col">Sub-division</th>
                            <th scope="col">Status</th>
                            <th scope="col">Assign</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($collectors as $collector)
                            @php $assignment = $collector->collectorAssignment; @endphp
                            <tr>
                                <td>
                                    {{ $collector->name }}
                                    <span class="small muted" style="display: block;">{{ $collector->email }}</span>
                                </td>
                                <td class="small">
                                    @if ($assignment)
                                        {{ $assignment->locality?->name }}
                                        <span class="muted">({{ $assignment->locality?->district?->name }})</span>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    @if ($assignment?->is_active)
                                        <span class="status ok">Signed</span>
                                    @elseif ($assignment)
                                        <span class="status bad">Revoked</span>
                                    @else
                                        <span class="status">Unassigned</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="post" action="{{ route('admin.collectors.assign') }}" class="inline-form">
                                        @csrf
                                        <input type="hidden" name="user_id" value="{{ $collector->id }}">
                                        <label class="small" for="locality-{{ $collector->id }}" style="position:absolute;left:-9999px;">Sub-division</label>
                                        <select id="locality-{{ $collector->id }}" name="locality_id" required style="max-width: 220px;">
                                            <option value="">Sub-division…</option>
                                            @foreach ($localities as $locality)
                                                <option value="{{ $locality->id }}" @selected($assignment?->locality_id === $locality->id)>
                                                    {{ $locality->name }} — {{ $locality->district?->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        <button class="button" type="submit">Sign</button>
                                    </form>
                                    @if ($assignment?->is_active)
                                        <form method="post" action="{{ route('admin.collectors.revoke', $assignment) }}" style="margin-top: var(--adm-space-1);">
                                            @csrf
                                            <button class="button secondary" type="submit">Unassign</button>
                                        </form>
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
