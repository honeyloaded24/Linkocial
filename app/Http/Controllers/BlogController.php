<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;

class BlogController extends Controller
{
    public function index()
    {
        $posts = Blog::where('status', 'published')
            ->orderBy('created_at', 'desc')
            ->paginate(9);

        return view('info.blog', compact('posts'));
    }

    public function show($slug)
    {
        $post = Blog::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();

        // Increment views
        $post->increment('views');

        return view('info.blog-post', compact('post'));
    }
}
