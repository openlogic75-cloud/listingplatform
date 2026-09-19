@extends('layouts.admin')

@section('title', $post ? 'Edit story' : 'New story')
@section('heading', $post ? 'Edit story' : 'New story')
@section('lede', 'Plain text with blank lines between paragraphs. Links and images are not embedded in the body.')

@section('content')
    <div class="stack">
        <form class="card" method="post"
              action="{{ $post ? route('admin.posts.update', $post) : route('admin.posts.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($post)
                @method('PUT')
            @endif

            <div class="field">
                <label for="post-title">Title</label>
                <input id="post-title" name="title" type="text" required maxlength="160"
                       value="{{ old('title', $post?->title) }}"
                       placeholder="e.g. A new agro farm in Medziphema">
                @error('title')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="post-excerpt">Short summary <span class="hint">(optional — shown in the list)</span></label>
                <input id="post-excerpt" name="excerpt" type="text" maxlength="500"
                       value="{{ old('excerpt', $post?->excerpt) }}">
                @error('excerpt')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="post-body">Story</label>
                <textarea id="post-body" name="body" rows="14" required maxlength="20000"
                          placeholder="Write about the business or farm: where it is, what it sells, why it is worth supporting.">{{ old('body', $post?->body) }}</textarea>
                @error('body')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="post-vendor">Featured business <span class="hint">(optional)</span></label>
                <select id="post-vendor" name="vendor_id">
                    <option value="">None</option>
                    @foreach ($vendors as $vendor)
                        <option value="{{ $vendor->id }}" @selected((string) old('vendor_id', $post?->vendor_id) === (string) $vendor->id)>
                            {{ $vendor->display_name }}
                        </option>
                    @endforeach
                </select>
                @error('vendor_id')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="post-cover">Cover image <span class="hint">(optional — JPEG, PNG or WebP, converted to WebP)</span></label>
                <input id="post-cover" name="cover_image" type="file"
                       accept="image/jpeg,image/png,image/webp">
                @if ($post?->cover_image)
                    <p class="hint">Current: {{ $post->cover_image }}</p>
                @endif
                @error('cover_image')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="field">
                <label for="post-status">Status</label>
                <select id="post-status" name="status" required>
                    <option value="draft" @selected(old('status', $post?->status ?? 'draft') === 'draft')>Draft — not on the site</option>
                    <option value="published" @selected(old('status', $post?->status) === 'published')>Published — live on the site</option>
                </select>
                @error('status')<p class="field-error" role="alert">{{ $message }}</p>@enderror
            </div>

            <div class="inline-form">
                <button class="button" type="submit">{{ $post ? 'Save changes' : 'Create story' }}</button>
                <a class="button secondary" href="{{ route('admin.posts.index') }}">Cancel</a>
            </div>
        </form>
    </div>
@endsection
