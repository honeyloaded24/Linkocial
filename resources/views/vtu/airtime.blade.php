@extends('layouts.app')

@section('title', 'Buy Airtime')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0 fw-bold">Airtime Top-up</h4>
            <p class="text-muted mb-0">Instantly recharge your mobile phone</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success bg-success text-white border-0 rounded-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger bg-danger text-white border-0 rounded-3">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Purchase Form -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ route('vtu.airtime.purchase') }}" method="POST">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="network" class="form-label fw-bold">Network<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="network" name="network" required>
                                <option value="" disabled selected>Select Network</option>
                                <option value="1">MTN</option>
                                <option value="2">GLO</option>
                                <option value="3">9Mobile</option>
                                <option value="4">Airtel</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">Phone Number<span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="Enter Phone Number" required>
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">Amount (₦)<span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="amount" name="amount" placeholder="Enter Amount (e.g. 100)" min="50" required>
                            
                            @php
                                $markup = (float) \App\Models\Setting::get('markup_vtu_airtime', 0);
                            @endphp
                            @if($markup > 0)
                                <small class="text-muted d-block mt-2">
                                    Total charge: <strong class="text-primary fs-6" id="totalCharge">₦0.00</strong> <span style="font-size: 0.75rem;">(includes {{ $markup }}% markup)</span>
                                </small>
                                <script>
                                    document.getElementById('amount').addEventListener('input', function(e) {
                                        let val = parseFloat(e.target.value) || 0;
                                        let total = val * (1 + ({{ $markup }} / 100));
                                        document.getElementById('totalCharge').innerText = '₦' + total.toFixed(2);
                                    });
                                </script>
                            @endif
                        </div>

                        <div class="d-grid gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold" style="background: #110077; border-color: #110077;">Buy Airtime</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Information / Instructions -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-4" style="background: #f8f9fa;">
                <div class="card-body p-4">
                    <p class="fs-5 text-dark mb-0 lh-lg" style="color: #4a5568 !important;">
                        <i class="fa-solid fa-circle-info text-primary mb-3 fs-2"></i><br>
                        Top-up your phone securely. Transactions are processed <strong>instantly</strong>. Minimum amount is ₦50.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
