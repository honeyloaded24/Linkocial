@extends('layouts.marketing')

@section('header_title', 'Frequently Asked Questions')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="premium-card p-5 shadow-sm">
                <div class="text-center mb-5">
                    <h1 class="fw-bold display-5">Frequently Asked Questions</h1>
                    <p class="text-muted">Everything you need to know about Linkocial growth.</p>
                </div>

                <div class="accordion accordion-flush" id="faqAccordion">
                    <!-- General Questions -->
                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Is it safe to buy followers and engagement?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse show" data-bs-parent="#faqAccordion">
                            <div class="accordion-body lh-lg text-secondary pb-4">
                                Yes, 100%. We use safe, organic-style delivery methods that comply with social media platform guidelines. We never ask for your password, and our services are designed to protect your account's integrity.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                How long does delivery take?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body lh-lg text-secondary pb-4">
                                Most orders start processing within minutes of order confirmation. For larger packages, we deliver in a natural, drip-feed manner to ensure the growth looks authentic and safe for your profile.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                Will my followers drop over time?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body lh-lg text-secondary pb-4">
                                We pride ourselves on high-retention services. While minor drops can happen due to platform updates, we offer a refill guarantee on our premium packages to ensure your numbers remain stable.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq4">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="faq4" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body lh-lg text-secondary pb-4">
                                We accept a wide range of secure payment methods, including major Credit/Debit cards, PayPal, and Cryptocurrency. All transactions are PCI-DSS compliant and encrypted for your safety.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border-bottom">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq5">
                                Can I buy services for multiple accounts?
                            </button>
                        </h2>
                        <div id="faq5" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body lh-lg text-secondary pb-4">
                                Absolutely. You can place separate orders for as many profiles or tracks as you like. Each order is handled independently by our automated delivery engine.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-5 text-center p-4 bg-soft rounded-4">
                    <h5 class="fw-bold">Still have questions?</h5>
                    <p class="text-muted small">Our support team is available 24/7 to help you dominate your social presence.</p>
                    <a href="{{ route('login') }}" class="btn btn-premium btn-sm mt-2">Open a Support Ticket</a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .accordion-button:not(.collapsed) {
        background-color: transparent;
        color: var(--primary);
        box-shadow: none;
    }
    .accordion-button:focus {
        box-shadow: none;
        border-color: rgba(0,0,0,0.1);
    }
</style>
@endsection
