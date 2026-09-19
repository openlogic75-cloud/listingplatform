@extends('layouts.admin')

@section('title', 'Blog')
@section('heading', 'Blog & community stories')
@section('lede', 'Write about new businesses and farms to promote them. Only published stories appear on the public site.')

@section('content')
    <div class="stack">
        <section class="card">
            <header>
                <div>
                    <h2>Stories</h2>
                    <p>{{ $posts->count() }} {{ \Illuminate\Support\Str::plural('story', $posts->count()) }} in total.</p>
                </div>
                <a class="button" href="{{ route('admin.posts.create') }}">New story</a>
            </header>

            @if ($posts->isEmpty())
                <p class="muted">No stories yet. Write the first one above.</p>
            @else
                <table>
                    <thead>
                        <tr>
                            <th scope="col">Title</th>
                            <th scope="col">Status</th>
                            <th scope="col">Published</th>
                            <th scope="col">Featured business</th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($posts as $post)
                            <tr>
                                <td>
                                    {{ $post->title }}
                                    <span class="small muted" style="display: block;">/blog/{{ $post->slug }}</span>
                                </td>
                                <td>
                                    @if ($post->status === 'published')
                                        <span class="status ok">Published</span>
                                    @else
                                        <span class="status">Draft</span>
                                    @endif
                                </td>
                                <td class="small muted">{{ $post->published_at?->format('j M Y') ?? '—' }}</td>
                                <td class="small muted">{{ $post->vendor?->display_name ?? '—' }}</td>
                                <td>
                                    <div class="inline-form">
                                        <a class="button secondary" href="{{ route('admin.posts.edit', $post) }}">Edit</a>
                                        <form method="post" action="{{ route('admin.posts.destroy', $post) }}"
                                              onsubmit="return confirm('Delete this story?');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="button secondary" type="submit">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </section>
    </div>
@endsection
