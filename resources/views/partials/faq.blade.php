<section class="py-5" style="background-color: #d1d5ff;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h2 class="fw-bold fs-1 mb-3">Frequently Asked Questions</h2>
            <p class="text-muted fs-5">Everything you need to know about our growth services.</p>
        </div>

        <div class="faq-container mx-auto" style="max-width: 800px;">
            <!-- FAQ Item 1 -->
            <div class="faq-item mb-4">
                <button class="faq-trigger w-100 p-4 rounded-4 bg-white border-black d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 italic-text text-start">Am I actually going to be receiving real followers?</span>
                    <i class="fa-solid fa-plus transition-icon"></i>
                </button>
                <div class="faq-answer bg-white border-black-top rounded-bottom-4 px-4 overflow-hidden">
                    <div class="py-4 text-muted">
                        We aim to provide high-quality accounts that feature profile pictures and active-looking presence. Our goal is to ensure your account maintains a natural and credible appearance as you grow your influence.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 2 -->
            <div class="faq-item mb-4">
                <button class="faq-trigger w-100 p-4 rounded-4 bg-white border-black d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 italic-text text-start">Do you offer any guarantee?</span>
                    <i class="fa-solid fa-plus transition-icon"></i>
                </button>
                <div class="faq-answer bg-white border-black-top rounded-bottom-4 px-4 overflow-hidden">
                    <div class="py-4 text-muted">
                        Certainly. We stand behind the quality of our platform, and we want you to feel fully confident in your growth. That's why we prioritize immediate processing, with many orders showing results just minutes after purchase. In the rare event that your counts fluctuate during our coverage period, our refill system is always available to replenish them at no extra cost. We're here to ensure your brand's momentum stays consistent.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 3 -->
            <div class="faq-item mb-4">
                <button class="faq-trigger w-100 p-4 rounded-4 bg-white border-black d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 italic-text text-start">How do more followers help my brand or business?</span>
                    <i class="fa-solid fa-plus transition-icon"></i>
                </button>
                <div class="faq-answer bg-white border-black-top rounded-bottom-4 px-4 overflow-hidden">
                    <div class="py-4 text-muted">
                        A larger following can act as social proof. When new visitors see an established audience, it can help build an initial layer of trust, making them more comfortable engaging with your brand or content organically.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 4 -->
            <div class="faq-item mb-4">
                <button class="faq-trigger w-100 p-4 rounded-4 bg-white border-black d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 italic-text text-start">Does buying followers put my account at risk?</span>
                    <i class="fa-solid fa-plus transition-icon"></i>
                </button>
                <div class="faq-answer bg-white border-black-top rounded-bottom-4 px-4 overflow-hidden">
                    <div class="py-4 text-muted">
                        Our methods are designed with account safety as a top priority. We have an extensive track record of successful deliveries and use industry-standard practices that aim to work harmoniously with platform guidelines. We never require your password, keeping your security entirely in your hands.
                    </div>
                </div>
            </div>

            <!-- FAQ Item 5 -->
            <div class="faq-item mb-4">
                <button class="faq-trigger w-100 p-4 rounded-4 bg-white border-black d-flex justify-content-between align-items-center">
                    <span class="fw-bold fs-5 italic-text text-start">What is your customer service like?</span>
                    <i class="fa-solid fa-plus transition-icon"></i>
                </button>
                <div class="faq-answer bg-white border-black-top rounded-bottom-4 px-4 overflow-hidden">
                    <div class="py-4 text-muted">
                        Our support team is available 24/7 to help with any questions. Whether you're curious about a service or need assistance with an existing order, we are here to ensure your experience with our platform is seamless.
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<style>
    .border-black {
        border: 2px solid #000000 !important;
        box-shadow: 0 4px 0 #000000;
        transition: all 0.2s;
    }
    .border-black-top {
        border: 2px solid #000000;
        border-top: none;
        box-shadow: 0 4px 0 #000000;
        margin-top: -10px;
    }
    .faq-trigger {
        position: relative;
        z-index: 2;
        outline: none;
    }
    .faq-trigger:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 0 #000000;
    }
    .faq-answer {
        max-height: 0;
        transition: max-height 0.3s ease-out;
        z-index: 1;
    }
    .faq-item.active .faq-answer {
        max-height: 500px;
    }
    .faq-item.active .faq-trigger {
        border-bottom-left-radius: 0 !important;
        border-bottom-right-radius: 0 !important;
        box-shadow: none;
    }
    .faq-item.active .transition-icon {
        transform: rotate(45deg);
    }
    .italic-text { font-style: italic; }
</style>

<script>
    document.querySelectorAll('.faq-trigger').forEach(trigger => {
        trigger.addEventListener('click', () => {
            const item = trigger.parentElement;
            const isActive = item.classList.contains('active');
            
            // Close all other items
            document.querySelectorAll('.faq-item').forEach(otherItem => {
                otherItem.classList.remove('active');
            });
            
            // Open clicked item if it wasn't open
            if (!isActive) {
                item.classList.add('active');
            }
        });
    });
</script>
