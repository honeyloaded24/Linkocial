@extends('layouts.app')

@section('header_title', 'Create New Insight')

@section('content')
<div class="container-fluid px-md-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="premium-card p-4 p-md-5 border-0 shadow-sm">
                <form action="{{ route('hq.blog.store') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    
                    <div class="row g-4">
                        <div class="col-md-8">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase tracking-wider">Article Title</label>
                                <input type="text" name="title" class="form-control form-control-lg rounded-3 @error('title') is-invalid @enderror" placeholder="e.g. How to Dominate TikTok in 2024" value="{{ old('title') }}" required>
                                @error('title') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase tracking-wider">Article Content</label>
                                <textarea name="content" rows="15" class="form-control rounded-3 @error('content') is-invalid @enderror" placeholder="Write your growth strategy here..." required>{{ old('content') }}</textarea>
                                @error('content') <div class="invalid-feedback">{{ $message }}</div> @enderror
                                
                                <!-- Markdown Cheat Sheet -->
                                <div class="mt-3 p-3 bg-light bg-opacity-50 rounded-3 border">
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <div class="small fw-bold text-primary mb-1">Headings</div>
                                            <code class="small d-block text-dark">## Subheading (H2)</code>
                                            <code class="small d-block text-dark">### Minor (H3)</code>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small fw-bold text-primary mb-1">Emphasis</div>
                                            <code class="small d-block text-dark">**Bold Text**</code>
                                            <code class="small d-block text-dark">*Italic Text*</code>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small fw-bold text-primary mb-1">Lists</div>
                                            <code class="small d-block text-dark">- Bullet Point</code>
                                            <code class="small d-block text-dark">1. Numbered List</code>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="small fw-bold text-primary mb-1">Links</div>
                                            <code class="small d-block text-dark">[Link Text](url)</code>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase tracking-wider">Category</label>
                                <input type="text" name="category" list="category_list" class="form-control rounded-3" placeholder="e.g. Growth" value="{{ old('category', 'Growth') }}">
                                <datalist id="category_list">
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat }}">
                                    @endforeach
                                </datalist>
                                <small class="text-muted mt-1 d-block">Select existing or type a new one</small>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase tracking-wider">Featured Image</label>
                                <div class="premium-card p-3 bg-light bg-opacity-50 border-dashed text-center">
                                    <i class="fa-solid fa-cloud-arrow-up text-primary fs-2 mb-2"></i>
                                    <input type="file" name="image" class="form-control form-control-sm mt-2">
                                    <small class="text-muted d-block mt-2">Max size: 2MB (JPG, PNG, WEBP)</small>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-bold small text-uppercase tracking-wider">Short Excerpt</label>
                                <textarea name="excerpt" rows="4" class="form-control rounded-3" placeholder="Brief summary for the blog listing page...">{{ old('excerpt') }}</textarea>
                            </div>

                            <div class="bg-primary bg-opacity-5 p-4 rounded-4">
                                <h6 class="fw-bold mb-3"><i class="fa-solid fa-magnifying-glass me-2"></i> SEO Settings</h6>
                                <div class="mb-3">
                                    <label class="form-label small">Meta Title</label>
                                    <input type="text" name="meta_title" class="form-control form-control-sm" value="{{ old('meta_title') }}">
                                </div>
                                <div class="">
                                    <label class="form-label small">Meta Description</label>
                                    <textarea name="meta_description" rows="3" class="form-control form-control-sm">{{ old('meta_description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="my-5 opacity-10">

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('hq.blog.index') }}" class="btn btn-light px-4 py-2 rounded-pill">Cancel</a>
                        <button type="submit" class="btn btn-primary-custom px-5 py-2 rounded-pill fw-bold">Publish Insight</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
