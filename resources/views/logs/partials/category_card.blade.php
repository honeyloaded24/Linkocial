@php
    $catCount = $category->active_products_count ?? $category->products_count ?? $category->products()->where('is_active', true)->count();
    $catUrl = route('logs.index', array_merge(request()->except('category', 'category_id', 'page'), ['category' => $category->id, 'tab' => 'products']));
@endphp
<div class="col category-grid-col" data-category-name="{{ strtolower($category->name) }}" data-category-id="{{ $category->id }}">
    <a href="{{ $catUrl }}" 
       class="category-card d-flex flex-column text-decoration-none h-100 p-3" 
       data-category-id="{{ $category->id }}"
       data-category-name="{{ $category->name }}"
       title="Browse {{ $category->name }} ({{ $catCount }} {{ \Illuminate\Support\Str::plural('product', $catCount) }})">
        <!-- Top-Left Category Icon -->
        <div class="category-card-icon mb-2">
            {!! $category->getIconHtml(44, 'rounded-3 shadow-2xs') !!}
        </div>

        <!-- Category Details: Name underneath icon, count underneath name -->
        <div class="mt-auto">
            <h6 class="category-card-name fw-bold mb-1" title="{{ $category->name }}">
                {{ $category->name }}
            </h6>
            <div class="category-card-count text-muted small">
                {{ $catCount }} {{ \Illuminate\Support\Str::plural('product', $catCount) }}
            </div>
        </div>
    </a>
</div>
