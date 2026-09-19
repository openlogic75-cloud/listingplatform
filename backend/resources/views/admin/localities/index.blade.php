@extends('layouts.admin')

@section('title', 'Districts and localities')
@section('heading', 'Districts and localities')
@section('lede', 'Every listing, rider base and job match references these rows by ID. Renaming an area changes one row here and nothing else in the system.')

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Add a district</h2>
                    <p>Start with the districts you operate in. Localities are added inside each one.</p>
                </div>
            </header>

            <form method="post" action="{{ route('admin.districts.store') }}" class="inline-form">
                @csrf
                <div class="field" style="margin: 0; flex: 1;">
                    <label for="district-name">District name</label>
                    <input id="district-name" name="name" type="text" required
                           maxlength="120" value="{{ old('name') }}"
                           placeholder="District name as people write it">
                </div>
                <button class="button" type="submit">Add district</button>
            </form>
        </section>

        @forelse ($districts as $district)
            <section class="card">
                <header>
                    <div>
                        <h2>{{ $district->name }}</h2>
                        <p>
                            {{ $district->localities->count() }}
                            {{ \Illuminate\Support\Str::plural('locality', $district->localities->count()) }}
                            ·
                            @if ($district->is_active)
                                <span class="status ok">Active</span>
                            @else
                                <span class="status bad">Hidden</span>
                            @endif
                        </p>
                    </div>
                </header>

                <form method="post"
                      action="{{ route('admin.districts.update', $district) }}"
                      class="inline-form">
                    @csrf
                    @method('PUT')
                    <label class="checkbox" for="district-{{ $district->id }}-name">
                        <span class="small">Rename</span>
                    </label>
                    <input id="district-{{ $district->id }}-name"
                           name="name" type="text" required maxlength="120"
                           value="{{ $district->name }}" style="width: 200px;">
                    <label class="checkbox" for="district-{{ $district->id }}-active">
                        <input id="district-{{ $district->id }}-active"
                               type="checkbox" name="is_active" value="1"
                               @checked($district->is_active)>
                        <span class="small">Active</span>
                    </label>
                    <button class="button secondary" type="submit">Save district</button>
                </form>

                @if ($district->localities->isEmpty())
                    <p class="muted small" style="margin: 0;">
                        No localities yet. Listings and rider bases cannot target this district
                        until at least one exists.
                    </p>
                @else
                    <table>
                        <caption class="muted small" style="text-align: left; padding-bottom: var(--adm-space-2);">
                            Localities inside {{ $district->name }}
                        </caption>
                        <thead>
                        <tr>
                            <th scope="col">Locality</th>
                            <th scope="col">Status</th>
                            <th scope="col">Rename or change status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($district->localities as $locality)
                            <tr>
                                <td>{{ $locality->name }}</td>
                                <td>
                                    @if ($locality->is_active)
                                        <span class="status ok">Active</span>
                                    @else
                                        <span class="status bad">Hidden</span>
                                    @endif
                                </td>
                                <td>
                                    <form method="post"
                                          action="{{ route('admin.localities.update', $locality) }}"
                                          class="inline-form">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" type="text" required maxlength="120"
                                               value="{{ $locality->name }}"
                                               aria-label="Name for {{ $locality->name }}"
                                               style="width: 180px;">
                                        <label class="checkbox"
                                               for="locality-{{ $locality->id }}-active">
                                            <input id="locality-{{ $locality->id }}-active"
                                                   type="checkbox" name="is_active" value="1"
                                                   @checked($locality->is_active)>
                                            <span class="small">Active</span>
                                        </label>
                                        <button class="button secondary" type="submit">Save</button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif

                <form method="post"
                      action="{{ route('admin.localities.store', $district) }}"
                      class="inline-form"
                      style="margin-top: var(--adm-space-4);">
                    @csrf
                    <div class="field" style="margin: 0; flex: 1;">
                        <label for="locality-name-{{ $district->id }}">
                            Add localities to {{ $district->name }}
                        </label>
                        <input id="locality-name-{{ $district->id }}"
                               name="name" type="text" required maxlength="2000"
                               placeholder="Centre, East, West">
                        <span class="hint">Separate multiple names with commas.</span>
                    </div>
                    <label class="checkbox" for="locality-active-{{ $district->id }}">
                        <input id="locality-active-{{ $district->id }}"
                               type="checkbox" name="is_active" value="1">
                        <span class="small">Active — we operate here</span>
                    </label>
                    <button class="button" type="submit">Add localities</button>
                </form>
            </section>
        @empty
            <section class="card">
                <h2>No districts yet</h2>
                <p class="muted">
                    Add the first district above, then add its localities. Until then the app
                    cannot offer location filters, rider bases or job matching.
                </p>
            </section>
        @endforelse
    </div>
@endsection