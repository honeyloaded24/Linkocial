@extends('layouts.app')

@section('header_title', 'Cable TV')

@section('content')
<div class="container-fluid">
    @include('partials.service_ticker', ['service' => 'vtu'])
    @include('partials.vtu_nav')

    <div class="row mb-4">
        <div class="col-12">
            <h4 class="mb-0 fw-bold">Cablesub</h4>
            <p class="text-muted mb-0">Subscribe to your favorite cable TV provider</p>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="row">
        <!-- Purchase Form -->
        <div class="col-12 col-lg-7 mb-4">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <form action="{{ route('vtu.cable.purchase') }}" method="POST" id="cableForm">
                        @csrf
                        
                        <!-- Cablename -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Cablename<span class="text-danger">*</span></label>
                            <select name="cablename" id="cablename" class="form-select form-select-lg" required>
                                <option value="" disabled selected>---------</option>
                                <option value="1">GOTV</option>
                                <option value="2">DSTV</option>
                                <option value="3">STARTIME</option>
                            </select>
                        </div>
                        
                        <!-- Cableplan -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Cableplan<span class="text-danger">*</span></label>
                            <select name="cableplan" id="cableplan" class="form-select form-select-lg" required>
                                <option value="" disabled selected>---------</option>
                            </select>
                        </div>
                        
                        <!-- Smart Card Number (IUC) -->
                        <div class="mb-4">
                            <label class="form-label fw-bold">Smart Card Number (IUC)<span class="text-danger">*</span></label>
                            <input type="text" name="smart_card_number" id="smart_card_number" class="form-select-lg form-control" placeholder="Enter Smart Card Number" required>
                            
                            <!-- Validation status container -->
                            <div id="validationResult" class="mt-2" style="display: none;">
                                <div class="p-3 rounded-3 bg-light border">
                                    <h6 class="fw-bold mb-1" id="customerName"></h6>
                                </div>
                            </div>
                        </div>

                        <!-- Amount (Hidden, submitted based on selected plan) -->
                        <input type="hidden" name="amount" id="amount">

                        <!-- Submit Button -->
                        <div class="d-grid gap-2">
                            <button type="button" id="validateBtn" class="btn btn-primary btn-lg fw-bold" style="background: #110077; border-color: #110077;">
                                Validate
                            </button>
                            <button type="submit" id="submitBtn" class="btn btn-success btn-lg fw-bold" style="display: none;">
                                Subscribe Now
                            </button>
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
                        You can contact DSTV/GOtv's customers care unit on <strong>01-2703232/08039003788</strong> or the toll free lines: <strong>08149860333, 07080630333, and 09090630333</strong> for assistance, STARTIMES's customers care unit on <strong>(094618888, 014618888)</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    // JSON Payload of all cable plans
    const cablePlansData = {
        "GOTV": [
            {"id":95,"cableplan_id":"95","cable":"GOTV","package":"GOtv Smallie - Monthly","plan_amount":"1900"},
            {"id":93,"cableplan_id":"93","cable":"GOTV","package":"GOtv Jinja","plan_amount":"3900"},
            {"id":96,"cableplan_id":"96","cable":"GOTV","package":"gotv-smallie-3months/quarterly","plan_amount":"5100"},
            {"id":94,"cableplan_id":"94","cable":"GOTV","package":"GOtv Jolli","plan_amount":"5800"},
            {"id":92,"cableplan_id":"92","cable":"GOTV","package":"GOtv Max","plan_amount":"8500"},
            {"id":97,"cableplan_id":"97","cable":"GOTV","package":"GOtv Smallie - Yearly","plan_amount":"15000"}
        ],
        "DSTV": [
            {"id":74,"cableplan_id":"74","cable":"DSTV","package":"DStv Padi","plan_amount":"4400"},
            {"id":75,"cableplan_id":"75","cable":"DSTV","package":"DStv Yanga","plan_amount":"6000"},
            {"id":85,"cableplan_id":"85","cable":"DSTV","package":"DStv Padi + ExtraView","plan_amount":"10400"},
            {"id":86,"cableplan_id":"86","cable":"DSTV","package":"DStv Padi + ExtraView","plan_amount":"10400"},
            {"id":76,"cableplan_id":"76","cable":"DSTV","package":"Dstv Confam","plan_amount":"11000"},
            {"id":84,"cableplan_id":"84","cable":"DSTV","package":"DStv Yanga + ExtraView","plan_amount":"12000"},
            {"id":83,"cableplan_id":"83","cable":"DSTV","package":"DStv Confam + ExtraView","plan_amount":"17000"},
            {"id":79,"cableplan_id":"79","cable":"DSTV","package":"DStv Asia","plan_amount":"18400"},
            {"id":77,"cableplan_id":"77","cable":"DSTV","package":"DStv  Compact","plan_amount":"19000"},
            {"id":88,"cableplan_id":"88","cable":"DSTV","package":"DStv Compact + Extra View","plan_amount":"25000"},
            {"id":89,"cableplan_id":"89","cable":"DSTV","package":"DStv Compact + French Touch","plan_amount":"26000"},
            {"id":80,"cableplan_id":"80","cable":"DSTV","package":"DStv Compact plus","plan_amount":"30000"},
            {"id":82,"cableplan_id":"82","cable":"DSTV","package":"DSTV PREMIUM-ASIA","plan_amount":"50500"},
            {"id":90,"cableplan_id":"90","cable":"DSTV","package":"DStv Premium + Extra View","plan_amount":"50500"},
            {"id":81,"cableplan_id":"81","cable":"DSTV","package":"DStv Premium-French","plan_amount":"69000"}
        ],
        "STARTIME": [
            {"id":103,"cableplan_id":"103","cable":"STARTIME","package":"Nova - 600 Naira - 1 Week","plan_amount":"600"},
            {"id":104,"cableplan_id":"104","cable":"STARTIME","package":"Basic - 1250 Naira - 1 Week","plan_amount":"1250"},
            {"id":105,"cableplan_id":"105","cable":"STARTIME","package":"Smart - 1300 Naira - 1 Week","plan_amount":"1300"},
            {"id":91,"cableplan_id":"91","cable":"STARTIME","package":"NOVA 1 MONTH","plan_amount":"2100"},
            {"id":101,"cableplan_id":"101","cable":"STARTIME","package":"Nova - 2,100 Naira - 1 Month","plan_amount":"2100"},
            {"id":106,"cableplan_id":"106","cable":"STARTIME","package":"Classic - 2300 Naira - 1 Week","plan_amount":"2300"},
            {"id":107,"cableplan_id":"107","cable":"STARTIME","package":"Super - 3000 Naira - 1 Week","plan_amount":"3000"},
            {"id":100,"cableplan_id":"100","cable":"STARTIME","package":"Smart - 3800 Naira - 1 Month","plan_amount":"3800"},
            {"id":99,"cableplan_id":"99","cable":"STARTIME","package":"Basic - 5,100 Naira - 1 Month","plan_amount":"5100"},
            {"id":98,"cableplan_id":"98","cable":"STARTIME","package":"Classic - 6800 Naira - 1 Mont","plan_amount":"6800"},
            {"id":102,"cableplan_id":"102","cable":"STARTIME","package":"Super - 9000 Naira - 1 Month","plan_amount":"9000"}
        ]
    };

    const cablenameSelect = document.getElementById('cablename');
    const cableplanSelect = document.getElementById('cableplan');
    const amountInput = document.getElementById('amount');
    const smartCardInput = document.getElementById('smart_card_number');
    const validateBtn = document.getElementById('validateBtn');
    const submitBtn = document.getElementById('submitBtn');
    const validationResult = document.getElementById('validationResult');
    const customerName = document.getElementById('customerName');

    // Update plans when cablename changes
    cablenameSelect.addEventListener('change', function() {
        const selectedId = this.value;
        const selectedText = this.options[this.selectedIndex].text;
        
        cableplanSelect.innerHTML = '<option value="" disabled selected>---------</option>';
        
        if (selectedText && cablePlansData[selectedText]) {
            const markupRate = {{ (float) \App\Models\Setting::get('markup_vtu_cable', 0) }};
            const multiplier = 1 + (markupRate / 100);

            cablePlansData[selectedText].forEach(plan => {
                const finalPrice = plan.plan_amount * multiplier;
                const option = document.createElement('option');
                option.value = plan.cableplan_id;
                option.textContent = `${plan.package} - ₦${markupRate > 0 ? finalPrice.toFixed(2) : plan.plan_amount}`;
                option.dataset.amount = plan.plan_amount;
                cableplanSelect.appendChild(option);
            });
        }
        
        resetValidation();
    });

    // Update amount when plan changes
    cableplanSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        if (selectedOption.dataset.amount) {
            amountInput.value = selectedOption.dataset.amount;
        }
        resetValidation();
    });

    // Reset validation when Smart Card Number changes
    smartCardInput.addEventListener('input', function() {
        resetValidation();
    });

    function resetValidation() {
        validationResult.style.display = 'none';
        submitBtn.style.display = 'none';
        validateBtn.style.display = 'block';
    }

    // Validate IUC
    validateBtn.addEventListener('click', function() {
        const cablename = cablenameSelect.value;
        const cableplan = cableplanSelect.value;
        const iuc = smartCardInput.value;

        if (!cablename || !cableplan || !iuc) {
            Swal.fire('Error', 'Please select a cable provider, a plan, and enter the smart card number.', 'error');
            return;
        }

        validateBtn.disabled = true;
        validateBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Validating...';

        fetch('{{ route("vtu.cable.validate") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify({
                cablename: cablename,
                smart_card_number: iuc
            })
        })
        .then(response => {
            if (!response.ok) {
                return response.json().then(err => {
                    throw new Error(err.message || 'Validation request failed');
                }).catch(() => {
                    throw new Error('Validation request failed with status ' + response.status);
                });
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                validationResult.style.display = 'block';
                customerName.textContent = 'Customer: ' + data.name;
                customerName.classList.remove('text-danger');
                customerName.classList.add('text-success');
                
                validateBtn.style.display = 'none';
                submitBtn.style.display = 'block';
                submitBtn.className = 'btn btn-success btn-lg fw-bold';
                submitBtn.innerText = 'Subscribe Now';
            } else {
                validationResult.style.display = 'block';
                customerName.textContent = data.message || 'Validation failed';
                customerName.classList.remove('text-success');
                customerName.classList.add('text-danger');
                
                // Allow user to proceed with subscription if validation unavailable
                submitBtn.style.display = 'block';
                submitBtn.className = 'btn btn-warning btn-lg fw-bold text-dark';
                submitBtn.innerText = 'Proceed With Subscription';
            }
        })
        .catch(error => {
            validationResult.style.display = 'block';
            customerName.textContent = error.message || 'Validation service temporarily unavailable.';
            customerName.classList.remove('text-success');
            customerName.classList.add('text-danger');
            
            // Allow user to proceed with subscription
            submitBtn.style.display = 'block';
            submitBtn.className = 'btn btn-warning btn-lg fw-bold text-dark';
            submitBtn.innerText = 'Proceed With Subscription';
        })
        .finally(() => {
            validateBtn.disabled = false;
            validateBtn.innerHTML = 'Validate';
        });
    });

    // Handle form submit
    document.getElementById('cableForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        Swal.fire({
            title: 'Confirm Subscription',
            html: `Are you sure you want to subscribe <b>${cableplanSelect.options[cableplanSelect.selectedIndex].text}</b> for <b>${smartCardInput.value}</b>?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#110077',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Yes, Subscribe!'
        }).then((result) => {
            if (result.isConfirmed) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Processing...';
                this.submit();
            }
        });
    });
</script>
@endpush

@endsection
