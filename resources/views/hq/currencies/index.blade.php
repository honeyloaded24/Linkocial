@extends('layouts.app')

@section('header_title', 'HQ: Currency Management')

@section('content')
<div class="row mb-4">
    <div class="col-lg-8">
        <div class="premium-card p-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Exchange Rates</h5>
                    <p class="text-muted small mb-0">Manage global currencies and set conversion rates relative to USD.</p>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success bg-success text-white border-0">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger bg-danger text-white border-0">{{ session('error') }}</div>
            @endif

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Symbol</th>
                            <th>Rate (1 USD)</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($currencies as $currency)
                        <tr>
                            <td>
                                <span class="fw-bold">{{ $currency->code }}</span>
                                @if($currency->is_default)
                                    <span class="badge bg-primary ms-2">DEFAULT</span>
                                @endif
                            </td>
                            <td><span class="badge bg-light text-dark border">{{ $currency->symbol }}</span></td>
                            <td>
                                <form action="{{ route('hq.currencies.update', $currency) }}" method="POST" class="d-flex gap-2">
                                    @csrf
                                    @method('PUT')
                                    <input type="number" name="exchange_rate" value="{{ $currency->exchange_rate }}" step="any" class="form-control form-control-sm" style="width: 120px;">
                                    <input type="hidden" name="symbol" value="{{ $currency->symbol }}">
                                    @if($currency->is_active) <input type="hidden" name="is_active" value="1"> @endif
                                    <button type="submit" class="btn btn-sm btn-outline-primary">Update</button>
                                </form>
                            </td>
                            <td>
                                @if($currency->is_active)
                                    <span class="badge bg-success">Active</span>
                                @else
                                    <span class="badge bg-secondary">Inactive</span>
                                @endif
                            </td>
                            <td>
                                <div class="d-flex gap-2">
                                    @if(!$currency->is_default)
                                        <form action="{{ route('hq.currencies.update', $currency) }}" method="POST">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_default" value="1">
                                            <input type="hidden" name="symbol" value="{{ $currency->symbol }}">
                                            <input type="hidden" name="exchange_rate" value="{{ $currency->exchange_rate }}">
                                            <button type="submit" class="btn btn-sm btn-outline-info">Make Default</button>
                                        </form>
                                        <form action="{{ route('hq.currencies.destroy', $currency) }}" method="POST" onsubmit="return confirm('Delete this currency?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa-solid fa-trash"></i></button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="premium-card p-4">
            <h5 class="fw-bold mb-3">Add New Currency</h5>
            <form action="{{ route('hq.currencies.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label small fw-bold">Currency Code (ISO)</label>
                    <input type="text" name="code" class="form-control" placeholder="e.g. EUR" maxlength="3" required>
                    <div class="form-text small">Use 3-letter ISO codes.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Currency Symbol</label>
                    <input type="text" name="symbol" class="form-control" placeholder="e.g. €" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Exchange Rate (vs 1 USD)</label>
                    <input type="number" name="exchange_rate" class="form-control" step="any" placeholder="e.g. 0.92" required>
                    <div class="form-text small">How much of this currency equals 1 USD.</div>
                </div>
                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_default" id="isDefaultAdd">
                        <label class="form-check-label small" for="isDefaultAdd">Set as System Default</label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary-custom w-100">Add Currency</button>
            </form>
        </div>
    </div>
</div>
@endsection
