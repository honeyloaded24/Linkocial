@extends('layouts.marketing')

@section('title', $post->seo_title . ' - Linkocial Blog')
@section('meta_description', $post->seo_description)
@section('header_title', $post->seo_title)

@section('content')
<div class="container py-5" style="padding-top: 100px !important;">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <!-- Article Header -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('landing') }}" class="text-decoration-none">Home</a></li>
                    <li class="breadcrumb-item"><a href="{{ route('info.blog') }}" class="text-decoration-none">Blog</a></li>
                    <li class="breadcrumb-item active" aria-current="page">{{ $post->category }}</li>
                </ol>
            </nav>

            <h1 class="display-4 fw-bold mb-4">{{ $post->title }}</h1>
            
            <div class="d-flex align-items-center gap-3 mb-5 pb-4 border-bottom">
                <div class="bg-soft rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                    <i class="fa-solid fa-user-tie text-primary"></i>
                </div>
                <div>
                    <div class="fw-bold">Linkocial Expert Team</div>
                    <div class="text-muted small">Published on {{ $post->created_at->format('M d, Y') }} &bull; {{ $post->views }} views</div>
                </div>
            </div>

            <!-- Featured Image -->
            @if($post->image)
                <div class="premium-card overflow-hidden mb-5 shadow-sm">
                    <img src="{{ $post->image_url }}" alt="{{ $post->title }}" class="img-fluid w-100" style="max-height: 500px; object-fit: cover;">
                </div>
            @endif

            <!-- Article Content -->
            <div class="blog-content lh-lg text-secondary mb-5">
                {!! $post->html_content !!}
            </div>

            <!-- Share & CTA -->
            <div class="premium-card p-5 bg-soft text-center mb-5 border-0">
                <h4 class="fw-bold mb-3">Ready to Apply These Strategies?</h4>
                <p class="text-muted mb-4">Scale your social presence instantly with our high-performance promotion services.</p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="{{ route('landing') }}#services" class="btn btn-premium px-5">Explore Services</a>
                    <a href="{{ route('register') }}" class="btn btn-outline-premium px-5">Get Started</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .blog-content {
        font-size: 1.1rem;
    }
    .blog-content p {
        margin-bottom: 1.5rem;
    }
    .blog-content h2, .blog-content h3 {
        color: var(--primary);
        font-weight: 800;
        margin-top: 2.5rem;
        margin-bottom: 1.25rem;
    }
    .breadcrumb-item a {
        color: var(--text-muted);
    }
    .breadcrumb-item.active {
        color: var(--primary);
        font-weight: 600;
    }
</style>
@endsection
