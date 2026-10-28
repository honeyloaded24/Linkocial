<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div class="d-flex flex-wrap gap-2">
        <a href="{{ route('orders.create') }}" class="btn {{ request()->routeIs('orders.create') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-rocket me-1"></i> Place BOOST Order
        </a>
        <a href="{{ route('orders.bulk') }}" class="btn {{ request()->routeIs('orders.bulk') ? 'btn-primary' : 'btn-outline-secondary' }} rounded-pill btn-sm px-3 fw-bold">
            <i class="fa-solid fa-layer-group me-1"></i> Bulk Order
        </a>
    </div>
    <div>
        <a href="{{ route('orders.index') }}" class="btn {{ request()->routeIs('orders.index') ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill btn-sm px-3 fw-bold shadow-sm">
            <i class="fa-solid fa-clock-rotate-left me-1"></i> Order History
        </a>
    </div>
</div>
