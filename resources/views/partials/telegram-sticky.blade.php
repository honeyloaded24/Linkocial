<!-- Telegram Sticky Support Widget -->
<style>
    .tg-sticky-btn {
        position: fixed;
        bottom: 25px;
        right: 25px;
        width: 60px;
        height: 60px;
        border-radius: 50%;
        background: linear-gradient(135deg, #0088cc, #00aaff);
        color: #ffffff !important;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 28px;
        box-shadow: 0 8px 24px rgba(0, 136, 204, 0.4);
        cursor: pointer;
        z-index: 9999;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        border: 2px solid rgba(255, 255, 136, 0.2);
    }
    .tg-sticky-btn:hover {
        transform: scale(1.1) rotate(5deg);
        box-shadow: 0 12px 30px rgba(0, 136, 204, 0.6);
        color: #ffffff !important;
    }
    .tg-sticky-btn::after {
        content: '';
        position: absolute;
        width: 100%;
        height: 100%;
        border-radius: 50%;
        border: 2px solid #0088cc;
        animation: tg-pulse 2s infinite;
        opacity: 0;
    }
    @keyframes tg-pulse {
        0% { transform: scale(1); opacity: 0.8; }
        100% { transform: scale(1.6); opacity: 0; }
    }

    .tg-popup-card {
        position: fixed;
        bottom: 95px;
        right: 25px;
        width: 330px;
        background: #1e293b;
        border: 1px solid rgba(255, 255, 255, 0.15);
        border-radius: 20px;
        padding: 24px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5);
        z-index: 9998;
        display: none;
        opacity: 0;
        transform: translateY(20px) scale(0.95);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        backdrop-filter: blur(16px);
        -webkit-backdrop-filter: blur(16px);
    }
    .tg-popup-card.active {
        display: block;
        opacity: 1;
        transform: translateY(0) scale(1);
    }
    .tg-popup-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 16px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        padding-bottom: 12px;
    }
    .tg-popup-title {
        color: #ffffff;
        font-weight: 700;
        font-size: 16px;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tg-close-btn {
        background: rgba(255, 255, 255, 0.1);
        border: none;
        color: #cbd5e1;
        width: 28px;
        height: 28px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.2s;
    }
    .tg-close-btn:hover {
        background: rgba(255, 255, 255, 0.2);
        color: #ffffff;
        transform: rotate(90deg);
    }
    .tg-popup-desc {
        color: #94a3b8;
        font-size: 13px;
        line-height: 1.5;
        margin-bottom: 20px;
    }
    .tg-action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        width: 100%;
        padding: 12px 16px;
        border-radius: 12px;
        font-weight: 600;
        font-size: 14px;
        text-decoration: none !important;
        transition: all 0.25s;
        margin-bottom: 12px;
    }
    .tg-btn-channel {
        background: linear-gradient(135deg, #0088cc, #0099e6);
        color: #ffffff !important;
        box-shadow: 0 4px 12px rgba(0, 136, 204, 0.3);
    }
    .tg-btn-channel:hover {
        background: linear-gradient(135deg, #0077b3, #0088cc);
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(0, 136, 204, 0.4);
        color: #ffffff !important;
    }
    .tg-btn-chat {
        background: rgba(255, 255, 255, 0.08);
        color: #38bdf8 !important;
        border: 1px solid rgba(56, 189, 248, 0.3);
    }
    .tg-btn-chat:hover {
        background: rgba(56, 189, 248, 0.15);
        border-color: #38bdf8;
        transform: translateY(-2px);
        color: #ffffff !important;
    }
</style>

<!-- Sticky Icon Button -->
<div class="tg-sticky-btn" id="tgStickyBtn" title="Need Help? Chat on Telegram">
    <i class="fa-brands fa-telegram"></i>
</div>

<!-- Popup Card -->
<div class="tg-popup-card" id="tgPopupCard">
    <div class="tg-popup-header">
        <h5 class="tg-popup-title">
            <i class="fa-brands fa-telegram text-info fs-5"></i> Telegram Community
        </h5>
        <button class="tg-close-btn" id="tgCloseBtn" type="button" aria-label="Close">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>
    <p class="tg-popup-desc">
        Join our official Telegram channel for exclusive updates & bonuses, or chat directly with our 24/7 support team!
    </p>
    
    <!-- Join Channel Button -->
    <a href="{{ \App\Models\Setting::get('telegram_channel_url', 'https://t.me/linkocial') }}" target="_blank" rel="noopener noreferrer" class="tg-action-btn tg-btn-channel">
        <i class="fa-brands fa-telegram fs-5"></i> Join Telegram Channel
    </a>
    
    <!-- Chat Directly Button -->
    <a href="{{ \App\Models\Setting::get('telegram_chat_url', 'https://t.me/linkocial_support') }}" target="_blank" rel="noopener noreferrer" class="tg-action-btn tg-btn-chat mb-0">
        <i class="fa-solid fa-comments fs-5"></i> Chat Directly on Telegram
    </a>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const tgBtn = document.getElementById('tgStickyBtn');
        const tgCard = document.getElementById('tgPopupCard');
        const tgClose = document.getElementById('tgCloseBtn');

        if (!tgBtn || !tgCard) return;

        tgBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            if (tgCard.classList.contains('active')) {
                tgCard.classList.remove('active');
                setTimeout(() => {
                    if (!tgCard.classList.contains('active')) tgCard.style.display = 'none';
                }, 300);
            } else {
                tgCard.style.display = 'block';
                setTimeout(() => tgCard.classList.add('active'), 10);
            }
        });

        if (tgClose) {
            tgClose.addEventListener('click', function(e) {
                e.stopPropagation();
                tgCard.classList.remove('active');
                setTimeout(() => {
                    if (!tgCard.classList.contains('active')) tgCard.style.display = 'none';
                }, 300);
            });
        }

        // Close when clicking outside
        document.addEventListener('click', function(e) {
            if (!tgCard.contains(e.target) && !tgBtn.contains(e.target) && tgCard.classList.contains('active')) {
                tgCard.classList.remove('active');
                setTimeout(() => {
                    if (!tgCard.classList.contains('active')) tgCard.style.display = 'none';
                }, 300);
            }
        });
    });
</script>
