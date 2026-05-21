<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<style>
    /* Premium Animated Gradient Background */
    @keyframes gradientBG {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
    }
    
    @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
    }

    #app-onboarding-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        /* Rich mesh gradient */
        background: linear-gradient(-45deg, #1e1b4b, #4338ca, #312e81, #1e40af);
        background-size: 400% 400%;
        animation: gradientBG 15s ease infinite;
        z-index: 99999;
        display: none; /* Hidden by default */
        flex-direction: column;
        color: white;
    }

    .onboarding-swiper {
        width: 100%;
        height: 100%;
    }

    .swiper-slide {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 2.5rem;
        text-align: center;
    }

    /* Glassmorphism Icon Wrapper */
    .onboarding-icon-wrapper {
        width: 140px;
        height: 140px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2.5rem;
        background: rgba(255, 255, 255, 0.1);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.2);
        color: white;
        font-size: 3.5rem;
        box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        animation: float 4s ease-in-out infinite;
    }

    .onboarding-title {
        font-family: var(--heading-font, inherit);
        font-weight: 900;
        font-size: 2rem;
        margin-bottom: 1rem;
        color: white;
        text-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }

    .onboarding-text {
        font-size: 1.15rem;
        color: rgba(255, 255, 255, 0.85);
        line-height: 1.6;
        max-width: 90%;
    }

    /* Custom Pagination */
    .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background: rgba(255, 255, 255, 0.4);
        opacity: 1;
    }
    
    .swiper-pagination-bullet-active {
        background: white;
        width: 30px;
        border-radius: 5px;
        transition: width 0.3s;
        box-shadow: 0 0 10px rgba(255,255,255,0.5);
    }

    .onboarding-footer {
        position: absolute;
        bottom: 2.5rem;
        left: 0;
        width: 100%;
        padding: 0 2rem;
        z-index: 10;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    /* Glass buttons */
    .btn-glass-next {
        background: rgba(255, 255, 255, 0.2);
        backdrop-filter: blur(10px);
        border: 1px solid rgba(255, 255, 255, 0.3);
        color: white !important;
        border-radius: 50px;
        padding: 0.6rem 1.5rem;
        font-weight: bold;
        transition: all 0.3s;
    }
    .btn-glass-next:active {
        background: rgba(255, 255, 255, 0.3);
    }

    .btn-skip {
        color: rgba(255, 255, 255, 0.7) !important;
    }
    
    #btn-get-started {
        background: white;
        color: #312e81;
        box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        border: none;
    }
    #btn-get-started:active {
        transform: scale(0.98);
    }
</style>

<div id="app-onboarding-overlay">
    <!-- Swiper -->
    <div class="swiper onboarding-swiper">
        <div class="swiper-wrapper">
            
            <!-- Slide 1 -->
            <div class="swiper-slide">
                <div class="onboarding-icon-wrapper">
                    <i class="fa-solid fa-rocket"></i>
                </div>
                <h2 class="onboarding-title">Welcome to Linkocial</h2>
                <p class="onboarding-text">Boost your social media presence with real engagement and powerful tools.</p>
            </div>

            <!-- Slide 2 -->
            <div class="swiper-slide">
                <div class="onboarding-icon-wrapper" style="animation-delay: 1s;">
                    <i class="fa-solid fa-phone-volume"></i>
                </div>
                <h2 class="onboarding-title">Virtual Numbers</h2>
                <p class="onboarding-text">Easily bypass SMS verifications with our temporary virtual numbers from any country.</p>
            </div>

            <!-- Slide 3 -->
            <div class="swiper-slide">
                <div class="onboarding-icon-wrapper" style="animation-delay: 2s;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h2 class="onboarding-title">Start Growing Today</h2>
                <p class="onboarding-text">Join thousands of users achieving their social media goals instantly.</p>
                
                <div class="mt-5 w-100 px-3">
                    <button class="btn rounded-pill w-100 py-3 fw-bold fs-5" id="btn-get-started">
                        Get Started <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

        </div>
        
        <div class="swiper-pagination mb-5 pb-4"></div>
        
        <div class="onboarding-footer">
            <button class="btn btn-link text-decoration-none fw-bold btn-skip" id="btn-skip-onboarding">Skip</button>
            <button class="btn btn-glass-next" id="btn-next-slide">Next</button>
        </div>
    </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        const isApp = navigator.userAgent.includes('LinkocialApp');
        const hasOnboarded = localStorage.getItem('app_onboarded') === 'true';
        
        // Debug override: remove `isApp &&` if you want to test in normal browser temporarily
        if (isApp && !hasOnboarded) {
            const overlay = document.getElementById('app-onboarding-overlay');
            overlay.style.display = 'flex';
            
            const swiper = new Swiper(".onboarding-swiper", {
                pagination: { el: ".swiper-pagination", clickable: true },
                on: {
                    slideChange: function () {
                        const footer = document.querySelector('.onboarding-footer');
                        if (this.isEnd) {
                            footer.style.opacity = '0';
                            footer.style.pointerEvents = 'none';
                        } else {
                            footer.style.opacity = '1';
                            footer.style.pointerEvents = 'auto';
                        }
                    }
                }
            });

            document.getElementById('btn-next-slide').addEventListener('click', () => swiper.slideNext());

            const completeOnboarding = () => {
                localStorage.setItem('app_onboarded', 'true');
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.4s ease';
                setTimeout(() => overlay.remove(), 400);
            };

            document.getElementById('btn-skip-onboarding').addEventListener('click', completeOnboarding);
            document.getElementById('btn-get-started').addEventListener('click', () => {
                completeOnboarding();
                if(window.location.pathname === '/') window.location.href = '/register';
            });
        }
    });
</script>
