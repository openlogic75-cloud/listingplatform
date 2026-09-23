@extends('layouts.admin')

@section('title', 'Listing approval')
@section('heading', 'Listing approval')
@section('lede', 'Review new vendor listings before they appear in the public catalog.')

@section('content')
    <section class="card">
        <header>
            <div>
                <h2>Pending listings</h2>
                <p>{{ $listings->total() }} waiting for review.</p>
            </div>
        </header>

        @if ($listings->isEmpty())
            <p class="muted">No listings are waiting for approval.</p>
        @else
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Listing</th>
                            <th scope="col">Vendor</th>
                            <th scope="col">Category</th>
                            <th scope="col">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($listings as $listing)
                            <tr>
                                <td>
                                    <strong>{{ $listing->title }}</strong>
                                    @if ($listing->description)
                                        <span class="small muted" style="display: block;">{{ \Illuminate\Support\Str::limit($listing->description, 100) }}</span>
                                    @endif
                                </td>
                                <td>{{ $listing->vendor?->display_name ?? 'Unknown vendor' }}</td>
                                <td>{{ \Illuminate\Support\Str::headline($listing->category) }}</td>
                                <td>
                                    <div class="row-actions">
                                        <form method="post" action="{{ route('admin.listings.approve', $listing) }}">
                                            @csrf
                                            <button class="button" type="submit">Approve</button>
                                        </form>
                                        <form method="post" action="{{ route('admin.listings.reject', $listing) }}">
                                            @csrf
                                            <button class="button secondary" type="submit">Reject</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{ $listings->links() }}
        @endif
    </section>
@endsection
