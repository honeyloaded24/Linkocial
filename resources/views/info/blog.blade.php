@extends('layouts.marketing')

@section('header_title', 'Linkocial Blog')

@section('content')
<div class="container py-5">
    <div class="text-center mb-5">
        <h1 class="fw-bold display-5">Growth Insights</h1>
        <p class="text-muted fs-5">Expert strategies and tips to dominate social media.</p>
    </div>

    <div class="row g-4 justify-content-center">
        @forelse($posts as $post)
            <div class="col-lg-4 col-md-6">
                <div class="premium-card h-100 overflow-hidden d-flex flex-column">
                    <a href="{{ $post->url }}" class="overflow-hidden d-block">
                        <div class="bg-soft" style="height: 220px;">
                            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="w-100 h-100" style="object-fit: cover; transition: transform 0.5s ease;">
                        </div>
                    </a>
                    <div class="p-4 flex-grow-1">
                        <div class="d-flex gap-2 mb-2">
                            <span class="badge bg-primary-soft text-primary small">{{ $post->category }}</span>
                            <span class="text-muted small">{{ $post->created_at->format('M d, Y') }}</span>
                        </div>
                        <a href="{{ $post->url }}" class="text-decoration-none text-main">
                            <h5 class="fw-bold mb-3 article-title-link">{{ $post->title }}</h5>
                        </a>
                        <p class="text-muted small mb-0">{{ $post->safe_excerpt }}</p>
                    </div>
                    <div class="p-4 border-top">
                        <a href="{{ $post->url }}" class="text-primary fw-bold text-decoration-none small">Read Article <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="premium-card p-5">
                    <i class="fa-solid fa-pen-nib text-muted fs-1 mb-3"></i>
                    <h4>New Insights Coming Soon</h4>
                    <p class="text-muted">Our experts are drafting the latest social media growth strategies. Check back shortly!</p>
                </div>
            </div>
        @endforelse
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-5">
        {{ $posts->links() }}
    </div>

    <!-- Newsletter Integration -->
    <div class="row justify-content-center mt-5 pt-4">
        <div class="col-lg-8">
            <div class="premium-card p-5 bg-soft text-center shadow-sm">
                <h4 class="fw-bold mb-3">Stay Ahead of the Algorithm</h4>
                <p class="text-muted mb-4">Join 5,000+ creators receiving weekly growth tips directly in their inbox.</p>
                <div class="d-flex gap-2 max-w-sm mx-auto">
                    <input type="email" class="form-control rounded-pill px-4 border-0 shadow-sm" placeholder="Your email address">
                    <button class="btn btn-premium rounded-pill px-4 shadow">Subscribe</button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .bg-primary-soft { background: rgba(99, 102, 241, 0.1); }
    .bg-danger-soft { background: rgba(220, 39, 67, 0.1); }
    .bg-success-soft { background: rgba(29, 185, 84, 0.1); }
    .max-w-sm { max-width: 500px; }
    
    .premium-card:hover img {
        transform: scale(1.08);
    }
    .article-title-link {
        transition: color 0.3s ease;
        color: var(--text-main);
    }
    .premium-card:hover .article-title-link {
        color: var(--primary);
    }
    .text-main { color: #0f172a; }
</style>
@endsection
