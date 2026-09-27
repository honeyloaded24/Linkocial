@extends('layouts.app')

@section('title', 'Pay Electricity Bill')

@section('content')
<div class="container-fluid">
    @include('partials.service_ticker', ['service' => 'vtu'])

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
                            <select class="form-select form-select-lg" id="disco" name="disco" required onchange="resetVerification()">
                                <option value="" disabled selected>Select Electricity Provider</option>
                                <option value="Ikeja Electric">Ikeja Electric (IKEDC - Lagos Mainland)</option>
                                <option value="Eko Electric">Eko Electric (EKEDC - Lagos Island & Lekki)</option>
                                <option value="Abuja Electric">Abuja Electric (AEDC - Abuja, Niger, Kogi, Nasarawa)</option>
                                <option value="Kano Electric">Kano Electric (KEDCO - Kano, Katsina, Jigawa)</option>
                                <option value="Enugu Electric">Enugu Electric (EEDC - Enugu, Abia, Imo, Anambra, Ebonyi)</option>
                                <option value="Port Harcourt Electric">Port Harcourt Electric (PHED - Rivers, Bayelsa, Cross River, Akwa Ibom)</option>
                                <option value="Ibadan Electric">Ibadan Electric (IBEDC - Oyo, Ogun, Osun, Kwara)</option>
                                <option value="Kaduna Electric">Kaduna Electric (KAEDCO - Kaduna, Kebbi, Sokoto, Zamfara)</option>
                                <option value="Jos Electric">Jos Electric (JED - Plateau, Bauchi, Benue, Gombe)</option>
                                <option value="Benin Electric">Benin Electric (BEDC - Edo, Delta, Ondo, Ekiti)</option>
                                <option value="Yola Electric">Yola Electric (YEDC - Adamawa, Borno, Taraba, Yobe)</option>
                                <option value="Aba Electric">Aba Electric (APLE - Aba Ring-fenced Area)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="meter_type" class="form-label fw-bold">Meter Type<span class="text-danger">*</span></label>
                            <select class="form-select form-select-lg" id="meter_type" name="meter_type" required onchange="resetVerification()">
                                <option value="Prepaid">Prepaid (Token Meter)</option>
                                <option value="Postpaid">Postpaid (Monthly Bill)</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label for="meter_number" class="form-label fw-bold">Meter Number<span class="text-danger">*</span></label>
                            <div class="input-group input-group-lg">
                                <input type="text" class="form-control border-end-0" id="meter_number" name="meter_number" placeholder="Enter Meter Number" required oninput="resetVerification()">
                                <button type="button" class="btn btn-outline-info border-start-0 border px-3" id="btnValidate" onclick="validateMeter()">
                                    <i class="fa-solid fa-check-circle me-1"></i> Verify Meter
                                </button>
                            </div>
                            <div id="meterName" class="mt-2" style="display: none;"></div>
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
    function resetVerification() {
        const meterNameLabel = document.getElementById('meterName');
        if (meterNameLabel) {
            meterNameLabel.style.display = 'none';
            meterNameLabel.innerHTML = '';
        }
    }

    function validateMeter() {
        const disco = document.getElementById('disco').value;
        const meterType = document.getElementById('meter_type').value;
        const meterNumber = document.getElementById('meter_number').value.trim();
        const meterNameLabel = document.getElementById('meterName');
        const btnValidate = document.getElementById('btnValidate');

        if (!disco) {
            alert('Please select your electricity distribution company (Disco).');
            return;
        }

        if (!meterNumber || meterNumber.replace(/\D/g, '').length < 6) {
            alert('Please enter a valid meter number (at least 6 digits).');
            return;
        }

        btnValidate.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Verifying...';
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
            btnValidate.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Verify Meter';
            btnValidate.disabled = false;

            meterNameLabel.style.display = 'block';

            if (data.success) {
                let displayText = '<div class="alert alert-success py-2 px-3 mb-0 rounded-3 small border-0 bg-success bg-opacity-10 text-success">';
                displayText += '<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-check fs-6"></i> <strong>' + data.name + '</strong></div>';
                if (data.address) {
                    displayText += '<div class="text-muted mt-1" style="font-size: 0.8rem;">' + data.address + '</div>';
                }
                if (data.detectedMismatch && data.suggestedMeterType) {
                    document.getElementById('meter_type').value = data.suggestedMeterType;
                    displayText += '<div class="mt-1"><span class="badge bg-warning text-dark"><i class="fa-solid fa-arrows-rotate me-1"></i> Auto-detected ' + data.suggestedMeterType + '</span></div>';
                }
                displayText += '</div>';
                meterNameLabel.innerHTML = displayText;
            } else {
                let errorHtml = '<div class="alert alert-danger py-2 px-3 mb-0 rounded-3 small border-0 bg-danger bg-opacity-10 text-danger">';
                errorHtml += '<div class="d-flex align-items-center gap-2"><i class="fa-solid fa-circle-exclamation fs-6"></i> <span>' + (data.message || 'Could not verify meter. Please double-check your provider and meter number.') + '</span></div>';
                errorHtml += '</div>';
                meterNameLabel.innerHTML = errorHtml;
            }
        })
        .catch(error => {
            btnValidate.innerHTML = '<i class="fa-solid fa-check-circle me-1"></i> Verify Meter';
            btnValidate.disabled = false;
            meterNameLabel.innerHTML = '<div class="alert alert-danger py-2 px-3 mb-0 rounded-3 small border-0 bg-danger bg-opacity-10 text-danger"><i class="fa-solid fa-triangle-exclamation me-1"></i> Connection timeout while verifying meter. You may still proceed with payment.</div>';
            meterNameLabel.style.display = 'block';
        });
    }
</script>
@endsection
