@extends('layouts.admin')

@section('title', $heading)
@section('heading', $heading)
@section('lede', $lede)

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Add {{ \Illuminate\Support\Str::plural($noun) }}</h2>
                    <p>Separate multiple names with commas.</p>
                </div>
            </header>

            <form method="post" action="{{ route($routeBase.'.store') }}" class="inline-form">
                @csrf
                <div class="field" style="margin: 0; flex: 1;">
                    <label for="category-name">{{ ucfirst($noun) }} name</label>
                    <input id="category-name" name="name" type="text" required maxlength="2000"
                           placeholder="{{ $placeholder }}" value="{{ old('name') }}">
                </div>
                <button class="button" type="submit">Add {{ \Illuminate\Support\Str::plural($noun) }}</button>
            </form>
        </section>

        <section class="card">
            <header>
                <div>
                    <h2>All {{ \Illuminate\Support\Str::plural($noun) }}</h2>
                    <p>{{ $categories->count() }} {{ \Illuminate\Support\Str::plural($noun, $categories->count()) }} in total.</p>
                </div>
            </header>

            @if ($categories->isEmpty())
                <p class="muted">Nothing here yet. Add the first one above.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th scope="col">{{ ucfirst($noun) }}</th>
                            <th scope="col">Status</th>
                            <th scope="col">{{ $usageLabel }}</th>
                            <th scope="col">Rename or change status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categories as $category)
                            <tr>
                                <td>{{ $category->name }}</td>
                                <td>
                                    @if ($category->is_active)
                                        <span class="status ok">Active</span>
                                    @else
                                        <span class="status bad">Retired</span>
                                    @endif
                                </td>
                                <td class="small muted">{{ $category->usage_count }}</td>
                                <td>
                                    <form method="post" action="{{ route($routeBase.'.update', $category) }}" class="inline-form">
                                        @csrf
                                        @method('PUT')
                                        <input name="name" type="text" required maxlength="80"
                                               value="{{ $category->name }}"
                                               aria-label="Name for {{ $category->name }}"
                                               style="width: 200px;">
                                        <label class="checkbox" for="category-{{ $category->id }}-active">
                                            <input id="category-{{ $category->id }}-active"
                                                   type="checkbox" name="is_active" value="1"
                                                   @checked($category->is_active)>
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
        </section>
    </div>
@endsection
