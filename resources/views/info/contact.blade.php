@extends('layouts.marketing')

@section('title', 'Contact Our Elite Support Team - ' . $siteName)

@section('content')
<section class="py-5 bg-soft">
    <div class="container py-5 text-center">
        <h1 class="display-4 fw-bold mb-3">Get in Touch</h1>
        <p class="text-muted lead mx-auto" style="max-width: 600px;">Our world-class support team is available 24/7 to help you dominate your social presence.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-5 justify-content-center">
            <div class="col-lg-5">
                <div class="premium-card p-5 border-0 shadow-lg h-100">
                    <div class="mb-5">
                        <div class="bg-primary bg-opacity-10 text-primary p-3 rounded-4 d-inline-block mb-4">
                            <i class="fa-solid fa-headset fa-2x"></i>
                        </div>
                        <h3 class="fw-bold mb-3">Direct Support</h3>
                        <p class="text-muted">For urgent inquiries regarding your orders or account, please log in and open a priority ticket for the fastest response.</p>
                        <a href="{{ route('tickets.create') }}" class="btn btn-premium px-4 mt-3">Open Support Ticket</a>
                    </div>

                    <div class="mb-0">
                        <h5 class="fw-bold mb-3">General Inquiries</h5>
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="bg-light p-2 rounded-circle"><i class="fa-solid fa-envelope text-primary"></i></div>
                            <div>
                                <div class="small text-muted">Email Us</div>
                                <div class="fw-bold">hello@linkocial.com</div>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2 rounded-circle"><i class="fa-solid fa-clock text-primary"></i></div>
                            <div>
                                <div class="small text-muted">Response Time</div>
                                <div class="fw-bold">Under 60 Minutes</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="premium-card p-5 border-0 shadow-lg h-100 bg-primary bg-opacity-5">
                    <h3 class="fw-bold mb-4">Frequently Asked Questions</h3>
                    <p class="text-muted mb-5">Quick answers to the most common questions about our high-performance services.</p>
                    
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-10">
                        <h6 class="fw-bold mb-2">How fast is delivery?</h6>
                        <p class="small text-muted mb-0">Most orders start instantly and complete within 1-2 hours of purchase.</p>
                    </div>
                    
                    <div class="mb-4 pb-4 border-bottom border-secondary border-opacity-10">
                        <h6 class="fw-bold mb-2">Are the followers real?</h6>
                        <p class="small text-muted mb-0">Yes, we provide high-quality, authentic-looking profiles to ensure your account remains safe and high-authority.</p>
                    </div>
                    
                    <div class="mb-0">
                        <h6 class="fw-bold mb-2">Do you offer refunds?</h6>
                        <p class="small text-muted mb-4">We offer a 100% satisfaction guarantee. If we can't deliver, we refund.</p>
                        <a href="{{ route('info.faq') }}" class="text-primary fw-bold text-decoration-none small">View Full FAQ <i class="fa-solid fa-arrow-right ms-1"></i></a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
