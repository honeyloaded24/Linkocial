@extends('layouts.app')

@section('title', 'Pay Electricity Bill')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0 fw-bold">Pay Electricity Bill</h4>
            <p class="text-muted mb-0">Recharge your prepaid or postpaid meter</p>
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
                    <form action="{{ route('vtu.electricity.purchase') }}" method="POST" id="electricityForm">
                        @csrf
                        
                        <div class="mb-4">
                            <label for="disco" class="form-label fw-bold">Electricity Provider (Disco)<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="disco" name="disco" required>
                                <option value="" disabled selected>Select Provider</option>
                                <option value="Ikeja Electric">Ikeja Electric</option>
                                <option value="Eko Electric">Eko Electric</option>
                                <option value="Abuja Electric">Abuja Electric</option>
                                <option value="Kano Electric">Kano Electric</option>
                                <option value="Enugu Electric">Enugu Electric</option>
                                <option value="Port Harcourt Electric">Port Harcourt Electric</option>
                                <option value="Ibadan Electric">Ibadan Electric</option>
                                <option value="Kaduna Electric">Kaduna Electric</option>
                                <option value="Jos Electric">Jos Electric</option>
                                <option value="Benin Electric">Benin Electric</option>
                                <option value="Yola Electric">Yola Electric</option>
                                <option value="Aba Electric">Aba Electric (APLE)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="meter_type" class="form-label fw-bold">Meter Type<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="meter_type" name="meter_type" required>
                                <option value="Prepaid">Prepaid</option>
                                <option value="Postpaid">Postpaid</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="meter_number" class="form-label fw-bold">Meter Number<span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control border-end-0" id="meter_number" name="meter_number" placeholder="Enter Meter Number" required>
                                <button type="button" class="btn btn-outline-info border-start-0 border" id="btnValidate" onclick="validateMeter()">
                                    <i class="fa-solid fa-check-circle me-1"></i> Verify
                                </button>
                            </div>
                            <small id="meterName" class="form-text text-success fw-bold mt-2" style="display: none;"></small>
                        </div>

                        <div class="mb-4">
                            <label for="amount" class="form-label fw-bold">Amount (₦)<span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-lg" id="amount" name="amount" placeholder="Enter Amount" min="500" required>
                            
                            @php
                                $markup = (float) \App\Models\Setting::get('markup_vtu_electricity', 0);
                            @endphp
                            @if($markup > 0)
                                <small class="text-muted d-block mt-2">
                                    Total to pay: <strong class="text-primary fs-6" id="totalCharge">₦0.00</strong>
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

                        <div class="mb-4">
                            <label for="phone" class="form-label fw-bold">Customer Phone<span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-lg" id="phone" name="phone" placeholder="Enter Customer Phone" required>
                        </div>

                        <div class="d-grid gap-2 mt-2">
                            <button type="submit" class="btn btn-primary btn-lg fw-bold" style="background: #110077; border-color: #110077;" id="btnSubmit">Pay Electricity Bill</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Information / Instructions -->
        <div class="col-12 col-lg-5 mb-4">
            <div class="card border-0 shadow-sm rounded-4" style="background: var(--bg-surface); border: 1px solid var(--border-color) !important;">
                <div class="card-body p-4">
                    <p class="fs-5 mb-0 lh-lg" style="color: var(--text-main);">
                        <i class="fa-solid fa-lightbulb text-warning mb-3 fs-2"></i><br>
                        Verify your meter number before making payment. Your meter will be recharged <strong>instantly</strong> upon successful payment.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function validateMeter() {
        const disco = document.getElementById('disco').value;
        const meterType = document.getElementById('meter_type').value;
        const meterNumber = document.getElementById('meter_number').value;
        const meterNameLabel = document.getElementById('meterName');
        const btnValidate = document.getElementById('btnValidate');

        if (!disco || !meterNumber || !meterType) {
            alert('Please select provider, meter type, and enter meter number.');
            return;
        }

        btnValidate.innerHTML = 'Verifying...';
        btnValidate.disabled = true;

        fetch("{{ route('vtu.electricity.validate') }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                disco: disco,
                meter_type: meterType,
                meter_number: meterNumber
            })
        })
        .then(response => response.json())
        .then(data => {
            btnValidate.innerHTML = 'Verify Meter';
            btnValidate.disabled = false;

            if (data.success) {
                meterNameLabel.style.display = 'block';
                let displayText = '<i class="fa-solid fa-circle-check me-1"></i> <strong>' + data.name + '</strong>';
                if (data.address) {
                    displayText += '<br><small class="text-muted">' + data.address + '</small>';
                }
                if (data.detectedMismatch && data.suggestedMeterType) {
                    document.getElementById('meter_type').value = data.suggestedMeterType;
                    displayText += '<br><span class="badge bg-warning text-dark mt-1"><i class="fa-solid fa-arrows-rotate me-1"></i> Auto-switched to ' + data.suggestedMeterType + '</span>';
                }
                meterNameLabel.innerHTML = displayText;
                meterNameLabel.className = 'form-text text-success fw-bold mt-2';
            } else {
                meterNameLabel.innerHTML = '<i class="fa-solid fa-circle-exclamation me-1"></i> ' + (data.message || 'Invalid meter number.');
                meterNameLabel.className = 'form-text text-danger fw-bold mt-2';
                meterNameLabel.style.display = 'block';
            }
        })
        .catch(error => {
            btnValidate.innerHTML = 'Verify Meter';
            btnValidate.disabled = false;
            meterNameLabel.innerHTML = 'Validation failed. Please try again.';
            meterNameLabel.className = 'form-text text-danger font-weight-bold mt-2';
            meterNameLabel.style.display = 'block';
        });
    }
</script>
@endsection
