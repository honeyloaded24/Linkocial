@php
    $serviceKey = $service ?? 'global';
    
    // Check service-specific note first
    $specificEnabled = (bool) \App\Models\Setting::get("notice_{$serviceKey}_enabled", false);
    $specificText = \App\Models\Setting::get("notice_{$serviceKey}_text");
    
    // Check global note fallback
    $globalEnabled = (bool) \App\Models\Setting::get("notice_global_enabled", false);
    $globalText = \App\Models\Setting::get("notice_global_text");
    
    $activeText = null;
    $badgeLabel = 'UPDATE';
    
    if ($specificEnabled && !empty(trim($specificText))) {
        $activeText = $specificText;
        if ($serviceKey === 'numbers') $badgeLabel = 'NUMBERS';
        elseif ($serviceKey === 'logs') $badgeLabel = 'MARKETPLACE';
        elseif ($serviceKey === 'smm') $badgeLabel = 'SERVICES';
        elseif ($serviceKey === 'vtu') $badgeLabel = 'VTU & BILLS';
    } elseif ($globalEnabled && !empty(trim($globalText))) {
        $activeText = $globalText;
        $badgeLabel = 'ANNOUNCEMENT';
    }
@endphp

@if($activeText)
<div class="service-news-ticker-container mb-4 shadow-sm rounded-4 overflow-hidden border" style="background: var(--bg-surface); border-color: var(--border-color) !important;">
    <div class="d-flex align-items-center py-2 px-3 gap-2">
        <!-- Live Badge -->
        <div class="ticker-badge flex-shrink-0 d-flex align-items-center gap-1.5 px-2.5 py-1 rounded-pill text-white shadow-sm" style="background: linear-gradient(135deg, #4f46e5 0%, #3b82f6 100%); font-size: 0.70rem; font-weight: 800; letter-spacing: 0.6px;">
            <span class="ticker-dot"></span>
            <i class="fa-solid fa-bullhorn" style="font-size: 0.72rem;"></i>
            <span>{{ $badgeLabel }}</span>
        </div>

        <!-- Ticker Marquee / Content -->
        <div class="ticker-marquee-track flex-grow-1 overflow-hidden position-relative" style="height: 24px; line-height: 24px;">
            <div class="ticker-marquee-content fw-semibold text-main text-nowrap" style="font-size: 0.88rem;">
                {!! $activeText !!}
            </div>
        </div>
    </div>
</div>

<style>
.ticker-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background-color: #22c55e;
    box-shadow: 0 0 6px #22c55e;
    animation: tickerPulse 1.8s infinite;
    display: inline-block;
}
@keyframes tickerPulse {
    0% { transform: scale(0.95); opacity: 0.8; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
    70% { transform: scale(1.15); opacity: 1; box-shadow: 0 0 0 5px rgba(34, 197, 94, 0); }
    100% { transform: scale(0.95); opacity: 0.8; box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}
.ticker-marquee-track:hover .ticker-marquee-content {
    animation-play-state: paused;
}
.ticker-marquee-content {
    display: inline-block;
    padding-left: 100%;
    animation: tickerScroll 24s linear infinite;
    will-change: transform;
}
@keyframes tickerScroll {
    0% { transform: translate3d(0, 0, 0); }
    100% { transform: translate3d(-100%, 0, 0); }
}
@media (max-width: 768px) {
    .ticker-marquee-content {
        animation-duration: 18s;
        font-size: 0.82rem !important;
    }
}
</style>
@endif
