@extends('layouts.app')

@section('header_title', 'Bulk Order')

@section('content')
<div class="row justify-content-center">
    <div class="col-12 col-lg-8">
        <div class="premium-card p-4 p-md-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold mb-0">Bulk Order Portal</h4>
                <a href="{{ route('orders.create') }}" class="btn btn-sm btn-outline-secondary rounded-pill px-3 fw-bold">Single Order</a>
            </div>

            <div class="alert bg-primary bg-opacity-10 border-0 mb-4">
                <h6 class="fw-bold text-primary mb-2"><i class="fa-solid fa-circle-info me-2"></i> How to use:</h6>
                <p class="small text-muted mb-0">
                    Enter one order per line in the following format:<br>
                    <code>SERVICE_ID | QUANTITY | LINK</code><br><br>
                    <strong>Example:</strong><br>
                    <code>102 | 1000 | https://instagram.com/p/123</code><br>
                    <code>45 | 500 | https://tiktok.com/@user</code>
                </p>
            </div>

            <form action="{{ route('orders.bulk.store') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label class="form-label fw-bold text-muted small text-uppercase">Bulk Data Input</label>
                    <textarea name="bulk_data" class="form-control" rows="12" placeholder="ID|Quantity|Link" required style="background-color: var(--bg-body); color: var(--text-main); border-color: var(--border-color); font-family: monospace; resize: none;"></textarea>
                </div>

                @if($errors->any())
                <div class="alert alert-danger border-0 mb-4 small">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                <div class="d-grid mt-4">
                    <button type="submit" class="btn btn-primary btn-lg fw-bold rounded-pill" style="background-color: var(--primary-color); border-color: var(--primary-color);">
                        <i class="fa-solid fa-boxes-stacked me-2"></i> Process Bulk Orders
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-12 col-lg-4 mt-4 mt-lg-0">
        <div class="premium-card p-4 h-100">
            <h6 class="fw-bold text-uppercase small text-muted mb-4" style="letter-spacing: 1px;">Service ID Reference</h6>
            <div class="table-responsive" style="max-height: 600px;">
                <table class="table table-sm table-hover align-middle">
                    <thead class="bg-light sticky-top">
                        <tr>
                            <th class="small py-2">ID</th>
                            <th class="small py-2">Service Name</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(\App\Models\Service::where('is_active', true)->orderBy('id')->get() as $service)
                        <tr>
                            <td class="fw-bold small text-primary">{{ $service->id }}</td>
                            <td class="small text-muted">{{ Str::limit($service->name, 40) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
