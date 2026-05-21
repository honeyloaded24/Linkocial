<!-- Swiper CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

<style>
    /* Native App Onboarding Overlay Styles */
    #app-onboarding-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100vw;
        height: 100vh;
        background-color: var(--bs-body-bg);
        z-index: 99999;
        display: flex;
        flex-direction: column;
        display: none; /* Hidden by default */
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
        padding: 2rem;
        text-align: center;
    }

    .onboarding-icon-wrapper {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 2rem;
        background: var(--bs-primary-bg-subtle);
        color: var(--bs-primary);
        font-size: 3rem;
    }

    .onboarding-title {
        font-family: var(--heading-font, inherit);
        font-weight: 800;
        font-size: 1.75rem;
        margin-bottom: 1rem;
        color: var(--bs-heading-color);
    }

    .onboarding-text {
        font-size: 1.1rem;
        color: var(--bs-secondary-color);
        line-height: 1.5;
        max-width: 80%;
    }

    .swiper-pagination-bullet {
        width: 10px;
        height: 10px;
        background: var(--bs-primary);
        opacity: 0.3;
    }
    
    .swiper-pagination-bullet-active {
        opacity: 1;
        width: 25px;
        border-radius: 5px;
        transition: width 0.3s;
    }

    .onboarding-footer {
        position: absolute;
        bottom: 2rem;
        left: 0;
        width: 100%;
        padding: 0 2rem;
        z-index: 10;
        display: flex;
        justify-content: space-between;
        align-items: center;
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
                <div class="onboarding-icon-wrapper" style="background: rgba(99, 102, 241, 0.1); color: #6366f1;">
                    <i class="fa-solid fa-phone-volume"></i>
                </div>
                <h2 class="onboarding-title">Virtual Numbers</h2>
                <p class="onboarding-text">Easily bypass SMS verifications with our temporary virtual numbers from any country.</p>
            </div>

            <!-- Slide 3 -->
            <div class="swiper-slide">
                <div class="onboarding-icon-wrapper" style="background: rgba(25, 135, 84, 0.1); color: #198754;">
                    <i class="fa-solid fa-chart-line"></i>
                </div>
                <h2 class="onboarding-title">Start Growing Today</h2>
                <p class="onboarding-text">Join thousands of users achieving their social media goals instantly.</p>
                
                <div class="mt-5 w-100">
                    <button class="btn btn-primary rounded-pill w-100 py-3 fw-bold fs-5 shadow" id="btn-get-started">
                        Get Started <i class="fa-solid fa-arrow-right ms-2"></i>
                    </button>
                </div>
            </div>

        </div>
        
        <div class="swiper-pagination mb-5 pb-3"></div>
        
        <div class="onboarding-footer">
            <button class="btn btn-link text-muted text-decoration-none fw-bold" id="btn-skip-onboarding">Skip</button>
            <button class="btn btn-light rounded-pill px-4 text-primary fw-bold" id="btn-next-slide">Next</button>
        </div>
    </div>
</div>

<!-- Swiper JS -->
<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        // 1. Check if user is on the App (via User-Agent)
        const isApp = navigator.userAgent.includes('LinkocialApp');
        
        // 2. Check if they have already seen the onboarding
        const hasOnboarded = localStorage.getItem('app_onboarded') === 'true';
        
        // 3. Only show if it's the app AND they haven't onboarded
        if (isApp && !hasOnboarded) {
            const overlay = document.getElementById('app-onboarding-overlay');
            overlay.style.display = 'flex'; // Show it
            
            // Initialize Swiper
            const swiper = new Swiper(".onboarding-swiper", {
                pagination: {
                    el: ".swiper-pagination",
                    clickable: true,
                },
                on: {
                    slideChange: function () {
                        // Hide footer buttons on the last slide
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

            // Button Event Listeners
            document.getElementById('btn-next-slide').addEventListener('click', () => {
                swiper.slideNext();
            });

            const completeOnboarding = () => {
                localStorage.setItem('app_onboarded', 'true');
                overlay.style.opacity = '0';
                overlay.style.transition = 'opacity 0.4s ease';
                setTimeout(() => overlay.remove(), 400); // fade out and remove
            };

            document.getElementById('btn-skip-onboarding').addEventListener('click', completeOnboarding);
            document.getElementById('btn-get-started').addEventListener('click', () => {
                completeOnboarding();
                // If they are on the landing page, redirect to register
                if(window.location.pathname === '/') {
                    window.location.href = '/register';
                }
            });
        }
    });
</script>
