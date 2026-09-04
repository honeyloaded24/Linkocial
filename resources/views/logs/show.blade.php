@extends('layouts.app')

@section('header_title', 'Purchase Product')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-6 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary rounded-pill px-4">
                    <i class="fa-solid fa-arrow-left me-2"></i> Back to Products
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            <div class="premium-card p-0 overflow-hidden shadow-sm border-0" style="border-radius: 12px; background: var(--bg-secondary);">
                <div class="bg-primary text-white p-4 text-center">
                    <h4 class="fw-bold mb-0">Order Summary</h4>
                </div>

                <form action="{{ route('logs.purchase') }}" method="POST">
                    @csrf
                    <div class="p-4">
                        <input type="hidden" name="product_id" value="{{ $product->id }}">
                        
                        <div class="d-flex align-items-center mb-4 p-3 rounded" style="background: rgba(0,0,0,0.2);">
                            <div class="bg-primary shadow-sm me-3 flex-shrink-0 d-flex align-items-center justify-content-center text-white" style="width: 50px; height: 50px; border-radius: 50%; font-size: 1.5rem;">
                                @if(stripos($product->name, 'instagram') !== false || stripos($product->name, 'ig') !== false)
                                    <i class="fa-brands fa-instagram"></i>
                                @elseif(stripos($product->name, 'facebook') !== false || stripos($product->name, 'fb') !== false)
                                    <i class="fa-brands fa-facebook"></i>
                                @elseif(stripos($product->name, 'twitter') !== false || stripos($product->name, 'x') !== false)
                                    <i class="fa-brands fa-x-twitter"></i>
                                @elseif(stripos($product->name, 'tiktok') !== false)
                                    <i class="fa-brands fa-tiktok"></i>
                                @elseif(stripos($product->name, 'linkedin') !== false)
                                    <i class="fa-brands fa-linkedin"></i>
                                @elseif(stripos($product->name, 'snapchat') !== false)
                                    <i class="fa-brands fa-snapchat"></i>
                                @elseif(stripos($product->name, 'vpn') !== false)
                                    <i class="fa-solid fa-shield-halved"></i>
                                @elseif(stripos($product->name, 'proxy') !== false)
                                    <i class="fa-solid fa-server"></i>
                                @else
                                    <i class="fa-solid fa-key"></i>
                                @endif
                            </div>
                            <div>
                                <div class="fw-bold text-primary fs-5">{{ $product->name }}</div>
                                <div class="text-muted d-block mt-1" style="font-size: 0.9em;">{!! nl2br(e($product->description)) !!}</div>
                            </div>
                        </div>

                        @php
                            $markupRate = (float) \App\Models\Setting::get('markup_logs_market', 20);
                            $multiplier = 1 + ($markupRate / 100);
                            $price = $product->our_price > 0 ? $product->our_price : ($product->unit_price * $multiplier);
                            $priceFormatted = money($price, true, 'NGN');
                            $priceNgnRaw = money($price, false, 'NGN');
                        @endphp

                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Price per unit</label>
                            <div class="fs-3 fw-bold text-success">{{ $priceFormatted }}</div>
                            <small class="text-muted d-block mt-1">Available Stock: <span class="fw-bold">{{ $product->in_stock }}</span> pcs</small>
                        </div>

                        <div class="mb-3 mt-4">
                            <label class="form-label text-muted small fw-bold">Quantity</label>
                            <input type="number" name="quantity" id="quantityInput" class="form-control form-control-lg bg-transparent border-secondary text-center fw-bold" value="1" min="1" max="{{ $product->in_stock }}" required style="color: var(--text-main);">
                        </div>

                        <div class="p-3 mt-4 rounded d-flex justify-content-between align-items-center" style="background: rgba(32, 201, 151, 0.1); border: 1px dashed rgba(32, 201, 151, 0.4);">
                            <span class="text-success fw-bold">Total Cost (NGN)</span>
                            <span class="fs-3 fw-bold text-success" id="totalCostDisplay">{{ $priceFormatted }}</span>
                        </div>
                    </div>
                    <div class="p-4 border-top border-secondary d-grid">
                        <button type="submit" class="btn btn-primary-custom btn-lg fw-bold" {{ $product->in_stock < 1 ? 'disabled' : '' }}>
                            {{ $product->in_stock < 1 ? 'Out of Stock' : 'Confirm Purchase' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const unitPrice = parseFloat('{{ $priceNgnRaw }}');
    
    function updateTotal() {
        let qty = parseInt(document.getElementById('quantityInput').value) || 1;
        let total = unitPrice * qty;
        
        let formatted = new Intl.NumberFormat('en-NG', { style: 'currency', currency: 'NGN' }).format(total);
        document.getElementById('totalCostDisplay').innerText = formatted;
    }

    document.getElementById('quantityInput').addEventListener('input', updateTotal);
</script>
@endpush
