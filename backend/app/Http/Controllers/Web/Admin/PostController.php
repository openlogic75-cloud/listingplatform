<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Vendor;
use App\Support\UploadValidator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Blog / community stories admin (M22.1). Admins write about new businesses
 * and farms to promote them; posts are drafted or published, with an
 * optional cover image and featured vendor.
 */
class PostController extends Controller
{
    public function index(): View
    {
        return view('admin.posts.index', [
            'posts' => Post::query()->with('vendor')->latest('id')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.posts.form', [
            'post' => null,
            'vendors' => $this->vendors(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $post = new Post;
        $post->fill($data);
        $post->slug = Post::uniqueSlug($data['title']);
        $post->author_id = $request->user()->id;
        $post->cover_image = $this->storeCover($request);
        $this->applyPublication($post, $request);
        $post->save();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', 'Story created.');
    }

    public function edit(Post $post): View
    {
        return view('admin.posts.form', [
            'post' => $post,
            'vendors' => $this->vendors(),
        ]);
    }

    public function update(Request $request, Post $post): RedirectResponse
    {
        $data = $this->validated($request);

        $post->fill($data);
        $post->slug = Post::uniqueSlug($data['title'], $post->id);

        if ($request->hasFile('cover_image')) {
            $post->cover_image = $this->storeCover($request);
        }

        $this->applyPublication($post, $request);
        $post->save();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', 'Story updated.');
    }

    public function destroy(Post $post): RedirectResponse
    {
        $post->delete();

        return redirect()
            ->route('admin.posts.index')
            ->with('status', 'Story deleted.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'excerpt' => ['nullable', 'string', 'max:500'],
            'body' => ['required', 'string', 'max:20000'],
            'vendor_id' => ['nullable', 'integer', 'exists:vendors,id'],
            'status' => ['required', 'string', 'in:'.implode(',', Post::STATUSES)],
            'cover_image' => ['nullable', 'file', 'max:'.UploadValidator::MAX_KILOBYTES],
        ]);
    }

    private function applyPublication(Post $post, Request $request): void
    {
        $post->status = (string) $request->input('status');

        if ($post->status === Post::STATUS_DRAFT) {
            $post->published_at = null;

            return;
        }

        $post->published_at ??= now();
    }

    private function storeCover(Request $request): ?string
    {
        if (! $request->hasFile('cover_image')) {
            return null;
        }

        return UploadValidator::validateAndStore($request->file('cover_image'), 'blog')['path'];
    }

    /**
     * @return Collection<int, Vendor>
     */
    private function vendors()
    {
        return Vendor::query()->orderBy('display_name')->get(['id', 'display_name']);
    }
}
