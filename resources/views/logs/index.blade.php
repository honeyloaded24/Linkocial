@extends('layouts.app')

@section('header_title', 'Logs & Accounts')

@push('styles')
<style>
    .log-card {
        transition: transform 0.2s, box-shadow 0.2s;
        border: 1px solid rgba(255, 255, 255, 0.05);
        background: var(--bg-secondary);
        border-radius: 12px;
    }
    .log-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .bag-icon {
        color: #20c997;
        font-size: 1.2rem;
        cursor: pointer;
    }
    .product-icon {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.2rem;
        color: #fff;
        flex-shrink: 0;
    }
    .search-btn {
        background-color: #212529;
        color: white;
    }
    .search-btn:hover {
        background-color: #1a1e21;
        color: white;
    }
    .log-title-link {
        color: var(--bs-primary, #6366f1);
        text-decoration: none;
        transition: color 0.15s ease, opacity 0.15s ease;
    }
    .log-title-link:hover {
        opacity: 0.85;
        text-decoration: underline !important;
        cursor: pointer;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">
    
    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Filters Section -->
        <div class="col-12 mb-4">
            <div class="d-flex flex-wrap flex-md-nowrap gap-3">
                <!-- Category Dropdown -->
                <div style="min-width: 260px;">
                    <select class="form-select text-white fw-bold border-0" style="background-color: #3b3f45; border-radius: 8px; padding: 12px 15px; cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.05);" onchange="window.location.href=this.value">
                        <option value="{{ route('logs.index', request()->except('category', 'category_id', 'page')) }}" {{ !$selectedCategory ? 'selected' : '' }}>Shop by Categories / All</option>
                        @foreach($categories as $category)
                            @php
                                $isSelected = ($selectedCategory && $selectedCategory->id == $category->id);
                            @endphp
                            <option value="{{ route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $category->id])) }}" {{ $isSelected ? 'selected' : '' }}>
                                {{ strtoupper($category->name) }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Search Bar -->
                <form action="{{ route('logs.index') }}" method="GET" class="flex-grow-1">
                    @if($selectedCategory)
                        <input type="hidden" name="category" value="{{ $selectedCategory->id }}">
                    @endif
                    <div class="input-group" style="border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.05); border: 1px solid rgba(0,0,0,0.1);">
                        <input type="text" name="search" class="form-control border-0" placeholder="Quick search products..." value="{{ request('search') }}" style="padding: 12px 20px;">
                        <button type="submit" class="btn search-btn px-4 border-0">
                            <i class="fa-solid fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Products Area -->
        <div class="col-12">
            
            <!-- Hot Products Section (Only show on 'All Products' view) -->
            @if(!$selectedCategory && $hotProducts->count() > 0)
            <div class="mb-4">
                <div class="bg-success text-white p-2 px-3 fw-bold rounded-top" style="font-size: 0.9rem;">
                    Our Recent Products <i class="fa-solid fa-fire text-warning ms-1"></i>
                </div>
                <div class="premium-card p-3 rounded-bottom border-top-0">
                    @foreach($hotProducts as $hot)
                        @include('logs.partials.product_card', ['product' => $hot])
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Regular Products -->
            <div class="mb-4">
                <div class="bg-success text-white p-2 px-3 fw-bold rounded-top" style="font-size: 0.9rem;">
                    {{ $selectedCategory ? strtoupper($selectedCategory->name) : 'ALL PRODUCTS' }}
                </div>
                <div class="premium-card p-3 rounded-bottom border-top-0" style="min-height: 300px;">
                    <!-- Search -->
                    <form action="{{ route('logs.index') }}" method="GET" class="mb-4">
                        @if($selectedCategory)
                            <input type="hidden" name="category" value="{{ $selectedCategory->id }}">
                        @endif
                        <div class="input-group">
                            <input type="text" name="search" class="form-control bg-transparent border-secondary" placeholder="Quick search products..." value="{{ request('search') }}" style="color: var(--text-main);">
                            <button class="btn btn-dark" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                        </div>
                    </form>

                    <div class="product-list">
                        @forelse($products as $product)
                            @include('logs.partials.product_card', ['product' => $product])
                        @empty
                            <div class="text-center py-5">
                                <i class="fa-solid fa-box-open fa-3x text-muted opacity-25 mb-3"></i>
                                <h6 class="text-muted">No products found in this category.</h6>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

@endsection
