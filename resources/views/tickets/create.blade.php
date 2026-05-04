@extends('layouts.app')

@section('header_title', 'Open New Ticket')

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="premium-card p-4">
                <div class="mb-4">
                    <h5 class="fw-bold mb-1">Create Support Ticket</h5>
                    <p class="text-muted small">Briefly describe your issue and we'll help you out.</p>
                </div>

                <form action="{{ route('tickets.store') }}" method="POST">
                    @csrf
                    
                    <div class="mb-3">
                        <label class="form-label text-muted fw-bold small text-uppercase">Subject</label>
                        <input type="text" name="subject" class="form-control border-secondary bg-transparent" placeholder="e.g. Order #123 not starting" required style="color: var(--text-main);">
                        @error('subject') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label text-muted fw-bold small text-uppercase">Priority</label>
                            <select name="priority" class="form-select border-secondary bg-transparent" style="color: var(--text-main);">
                                <option value="low">Low</option>
                                <option value="medium" selected>Medium</option>
                                <option value="high">High</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label text-muted fw-bold small text-uppercase">Message</label>
                        <textarea name="message" class="form-control border-secondary bg-transparent" rows="6" placeholder="Describe your issue in detail..." required style="color: var(--text-main);"></textarea>
                        @error('message') <small class="text-danger">{{ $message }}</small> @enderror
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary-custom px-5">Submit Ticket</button>
                        <a href="{{ route('tickets.index') }}" class="btn btn-outline-secondary px-4">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="premium-card p-4 bg-primary bg-opacity-10 border-0">
                <h6 class="fw-bold text-primary mb-3">Support Guidelines</h6>
                <ul class="small text-muted ps-3 mb-0">
                    <li class="mb-2">Please provide your Order ID if the issue relates to a specific purchase.</li>
                    <li class="mb-2">Most tickets are answered within 2-6 hours.</li>
                    <li class="mb-2">Check our FAQ before opening a ticket for general questions.</li>
                    <li>Be polite to our support staff to get faster assistance.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
