<footer class="border-top bg-white">
    
    <!-- Main Footer Content -->
    <div class="container py-5">
        <div class="row g-4 justify-content-between">
            <!-- Brand Column -->
            <div class="col-lg-3">
                <a class="navbar-brand d-flex align-items-center gap-2 mb-4" href="{{ route('landing') }}">
                    @if($siteLogoLight || $siteLogoDark)
                        <img src="{{ $siteLogoLight ?? $siteLogoDark }}" alt="{{ $siteName }}" height="32" class="logo-light">
                        <img src="{{ $siteLogoDark ?? $siteLogoLight }}" alt="{{ $siteName }}" height="32" class="logo-dark">
                    @else
                        <i class="fa-solid fa-bolt-lightning text-primary fs-3"></i>
                        <span class="fw-bold fs-4">{{ $siteName }}</span>
                    @endif
                </a>
                <p class="text-muted small mb-4 pe-lg-3">
                    {{ $siteName }} is the world's leading SMM platform, providing high-quality growth solutions that help brands and individuals dominate their social presence.
                </p>
                <div class="d-flex gap-3">
                    <a href="#" class="social-icon-btn"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" class="social-icon-btn"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" class="social-icon-btn"><i class="fa-brands fa-linkedin"></i></a>
                    <a href="#" class="social-icon-btn"><i class="fa-brands fa-facebook"></i></a>
                </div>
            </div>

            <!-- Links Column -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-4">Useful Links</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="{{ route('landing') }}">Home</a></li>
                    <li><a href="{{ route('info.about') }}">About</a></li>
                    <li><a href="{{ route('info.faq') }}">FAQ Support</a></li>
                    <li><a href="{{ route('info.blog') }}">Growth Blog</a></li>
                    <li><a href="{{ route('info.contact') }}">Contact</a></li>
                    <li><a href="{{ route('legal.terms') }}">Terms of Service</a></li>
                </ul>
            </div>

            <!-- Platforms Column -->
            <div class="col-lg-2">
                <h6 class="fw-bold mb-4">Platforms</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="{{ route('platform', ['platform' => 'instagram']) }}">Instagram</a></li>
                    <li><a href="{{ route('platform', ['platform' => 'tiktok']) }}">TikTok</a></li>
                    <li><a href="{{ route('platform', ['platform' => 'youtube']) }}">YouTube</a></li>
                    <li><a href="{{ route('platform', ['platform' => 'x']) }}">X (Twitter)</a></li>
                    <li><a href="{{ route('platform', ['platform' => 'facebook']) }}">Facebook</a></li>
                </ul>
            </div>

            <!-- Best Sellers Column -->
            <div class="col-lg-3">
                <h6 class="fw-bold mb-4">Best Sellers</h6>
                <ul class="list-unstyled footer-links">
                    <li><a href="{{ route('services.ig-followers') }}">Buy Instagram Followers</a></li>
                    <li><a href="{{ route('services.fb-followers') }}">Buy Facebook Followers</a></li>
                    <li><a href="{{ route('services.tk-followers') }}">Buy TikTok Followers</a></li>
                    <li><a href="{{ route('services.yt-subscribers') }}">Buy YouTube Subscribers</a></li>
                    <li><a href="{{ route('services.yt-watchtime') }}">Buy YouTube Watchtime</a></li>
                </ul>
            </div>

            <!-- Contact/Trust Column -->
            <div class="col-lg-2 text-lg-end">
                <h6 class="fw-bold mb-4">Support</h6>
                <p class="text-muted small mb-1">Available 24/7</p>
                <a href="{{ route('login') }}" class="text-primary fw-bold text-decoration-none small">Open a Ticket</a>
                <div class="mt-4">
                    <span class="d-block text-muted small mb-2">Verified Secure</span>
                    <i class="fa-solid fa-shield-check text-success fs-3"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="bg-soft py-4 border-top">
        <div class="container text-center">
            <p class="text-muted small mb-0">&copy; {{ date('Y') }} {{ $siteName }}. Professional SMM Infrastructure.</p>
        </div>
    </div>
</footer>

<style>
    .newsletter-card {
        background-color: #f8fafc;
        border: 1px solid var(--border) !important;
    }
    .social-icon-btn {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--text-muted);
        text-decoration: none;
        transition: all 0.3s;
    }
    .social-icon-btn:hover {
        background: var(--primary);
        color: white;
        border-color: var(--primary);
        transform: translateY(-3px);
    }
    .footer-links li {
        margin-bottom: 0.75rem;
    }
    .footer-links a {
        color: var(--text-muted);
        text-decoration: none;
        font-size: 0.9rem;
        transition: all 0.2s;
    }
    .footer-links a:hover {
        color: var(--primary);
        padding-left: 5px;
    }
</style>
