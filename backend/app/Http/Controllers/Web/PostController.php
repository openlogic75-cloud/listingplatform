<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Post;
use Illuminate\View\View;

/**
 * Public blog (M22.1): stories about businesses and farms. Only published
 * posts are visible; drafts 404.
 */
class PostController extends Controller
{
    public function index(): View
    {
        return view('pages.blog', [
            'posts' => Post::query()
                ->published()
                ->with('vendor')
                ->latest('published_at')
                ->get(),
        ]);
    }

    public function show(Post $post): View
    {
        abort_unless(
            $post->status === Post::STATUS_PUBLISHED && $post->published_at !== null,
            404,
        );

        return view('pages.post', [
            'post' => $post->load(['vendor', 'author']),
        ]);
    }
}
