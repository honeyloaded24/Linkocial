<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminBlogController extends Controller
{
    public function index()
    {
        $posts = Blog::orderBy('created_at', 'desc')->paginate(20);
        return view('hq.blog.index', compact('posts'));
    }

    public function create()
    {
        $categories = Blog::distinct()->pluck('category')->filter()->values();
        return view('hq.blog.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'category' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->title);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blogs', 'public');
        }

        Blog::create($data);

        return redirect()->route('hq.blog.index')->with('success', 'Blog post created successfully!');
    }

    public function edit($id)
    {
        $post = Blog::findOrFail($id);
        $categories = Blog::distinct()->pluck('category')->filter()->values();
        return view('hq.blog.edit', compact('post', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $post = Blog::findOrFail($id);

        $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
            'category' => 'required',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $data = $request->all();
        $data['slug'] = Str::slug($request->title);

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('blogs', 'public');
        }

        $post->update($data);

        return redirect()->route('hq.blog.index')->with('success', 'Blog post updated successfully!');
    }

    public function destroy($id)
    {
        $post = Blog::findOrFail($id);
        $post->delete();

        return redirect()->route('hq.blog.index')->with('success', 'Blog post deleted!');
    }
}
