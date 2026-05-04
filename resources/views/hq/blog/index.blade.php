@extends('layouts.app')

@section('header_title', 'Blog Management')

@section('content')
<div class="container-fluid px-md-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h5 class="fw-bold mb-0">Platform Insights</h5>
        <a href="{{ route('hq.blog.create') }}" class="btn btn-primary-custom px-4">
            <i class="fa-solid fa-plus me-2"></i> Create New Post
        </a>
    </div>

    <div class="premium-card p-0 overflow-hidden shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="bg-light bg-opacity-50">
                    <tr>
                        <th class="px-4">Post Title</th>
                        <th>Category</th>
                        <th>Views</th>
                        <th>Date Published</th>
                        <th class="text-end px-4">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($posts as $post)
                    <tr>
                        <td class="px-4">
                            <div class="d-flex align-items-center gap-3">
                                <div class="bg-soft rounded-3 overflow-hidden" style="width: 50px; height: 50px;">
                                    <img src="{{ $post->image_url }}" alt="" class="img-fluid w-100 h-100" style="object-fit: cover;">
                                </div>
                                <div>
                                    <div class="fw-bold">{{ $post->title }}</div>
                                    <small class="text-muted">{{ $post->slug }}</small>
                                </div>
                            </div>
                        </td>
                        <td><span class="badge bg-primary bg-opacity-10 text-primary">{{ $post->category }}</span></td>
                        <td><i class="fa-solid fa-eye me-1 text-muted small"></i> {{ number_format($post->views) }}</td>
                        <td class="small text-muted">{{ $post->created_at->format('M d, Y') }}</td>
                        <td class="text-end px-4">
                            <div class="dropdown">
                                <button class="btn btn-light btn-sm rounded-pill px-3" data-bs-toggle="dropdown">
                                    Options <i class="fa-solid fa-chevron-down ms-1 small"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end border-0 shadow-lg premium-card p-2">
                                    <li><a class="dropdown-item rounded" href="{{ route('info.blog.show', $post->slug) }}" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square me-2 text-primary"></i> View Post</a></li>
                                    <li><a class="dropdown-item rounded" href="{{ route('hq.blog.edit', $post->id) }}"><i class="fa-solid fa-pen-to-square me-2 text-warning"></i> Edit Article</a></li>
                                    <li><hr class="dropdown-divider opacity-10"></li>
                                    <li>
                                        <form action="{{ route('hq.blog.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this post?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="dropdown-item rounded text-danger"><i class="fa-solid fa-trash me-2"></i> Delete Post</button>
                                        </form>
                                    </li>
                                </ul>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">No blog posts found. Start building your authority today!</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-4">
        {{ $posts->links() }}
    </div>
</div>
@endsection
