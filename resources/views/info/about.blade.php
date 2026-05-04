@extends('layouts.marketing')

@section('header_title', 'About Linkocial')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="premium-card p-5 shadow-sm">
                <div class="text-center mb-5">
                    <h1 class="fw-bold display-5">About Linkocial</h1>
                    <p class="text-muted">Empowering Digital Growth Since 2018</p>
                </div>

                <div class="lh-lg text-secondary">
                    <h4 class="text-primary-custom fw-bold mt-4">Our Mission</h4>
                    <p>At Linkocial, our mission is simple: to provide high-quality, reliable, and lightning-fast social media growth solutions for individuals, brands, and influencers. We believe that everyone deserves an audience, and our infrastructure is built to bridge the gap between creative content and global reach.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">Why Choose Us?</h4>
                    <p>We aren't just another SMM panel. We are a team of digital strategists and infrastructure engineers who understand the nuances of social media algorithms. We focus on "Viral Velocity"—providing the engagement signals needed to trigger organic growth engines across Instagram, TikTok, YouTube, and beyond.</p>
                    
                    <ul class="mt-3">
                        <li><strong>Safe & Secure:</strong> We never ask for your passwords and use safe delivery methods.</li>
                        <li><strong>Instant Delivery:</strong> Our automated systems start processing orders in minutes.</li>
                        <li><strong>24/7 Support:</strong> Our dedicated team is always here to ensure your growth never stops.</li>
                    </ul>

                    <h4 class="text-primary-custom fw-bold mt-4">Our Infrastructure</h4>
                    <p>Linkocial operates on a proprietary network of high-quality accounts and engagement nodes. This allows us to maintain market-leading prices while delivering results that look natural and professional. Whether you are buying 100 followers or 1,000,000, our systems scale to meet your demands instantly.</p>

                    <h4 class="text-primary-custom fw-bold mt-4">Global Impact</h4>
                    <p>With over {{ number_format($stats['total_orders'] ?? 50000) }}+ orders successfully delivered, we have helped thousands of users achieve their digital goals. From independent artists reaching their first 10k listeners on Spotify to businesses establishing massive social proof on Facebook, Linkocial is the engine behind the world's fastest-growing profiles.</p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
