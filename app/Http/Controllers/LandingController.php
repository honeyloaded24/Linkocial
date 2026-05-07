<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class LandingController extends Controller
{
    public function __construct()
    {
        // Share global data with all marketing views
        view()->share([
            'channels' => \App\Models\Service::getChannels(),
            'siteName' => \App\Models\Setting::get('site_name', 'Linkocial'),
            'siteLogo' => \App\Models\Setting::get('site_logo_light'),
            'siteFavicon' => \App\Models\Setting::get('site_favicon'),
        ]);
    }

    /**
     * Show the application's premium landing page.
     */
    public function index()
    {
        $stats = [
            'total_orders' => \App\Models\Order::count() + 1000,
            'active_users' => \App\Models\User::count() + 250,
            'total_services' => \App\Models\Service::withActiveProvider()->count(),
        ];

        $channels = \App\Models\Service::getChannels();

        return view('landing', compact('stats', 'channels'));
    }

    /**
     * Show a dedicated page for a specific social media platform.
     */
    public function platform($slug)
    {
        // Normalize slug to platform name
        $name = ucfirst($slug);
        if ($slug == 'x') $name = 'X';
        if ($slug == 'youtube') $name = 'YouTube';
        if ($slug == 'tiktok') $name = 'TikTok';
        if ($slug == 'facebook') $name = 'Facebook';
        if ($slug == 'telegram') $name = 'Telegram';
        if ($slug == 'spotify') $name = 'Spotify';
        if ($slug == 'audiomack') $name = 'Audiomack';
        
        $services = \App\Models\Service::withActiveProvider()
            ->where('name', 'like', '%' . $name . '%')
            ->limit(12)
            ->get();

        if ($services->isEmpty() && !in_array($name, ['Instagram', 'TikTok', 'YouTube', 'Facebook', 'X', 'Telegram', 'Spotify', 'Audiomack'])) {
            return redirect()->route('landing');
        }

        // Get platform-specific metadata
        $meta = [
            'Instagram' => ['icon' => 'fa-brands fa-instagram', 'color' => 'linear-gradient(45deg, #f09433 0%, #e6683c 25%, #dc2743 50%, #cc2366 75%, #bc1888 100%)', 'desc' => 'Dominate Instagram with our premium promotion services. Scale your followers, likes, and engagement in minutes.'],
            'TikTok' => ['icon' => 'fa-brands fa-tiktok', 'color' => 'linear-gradient(45deg, #00f2ea, #ff0050)', 'desc' => 'Go viral on TikTok with our premium promotion services.'],
            'YouTube' => ['icon' => 'fa-brands fa-youtube', 'color' => '#FF0000', 'desc' => 'Grow your channel with high-retention promotion services, views and real subscribers.'],
            'Facebook' => ['icon' => 'fa-brands fa-facebook', 'color' => '#1877F2', 'desc' => 'Scale your Facebook page reach and engagement instantly with our promotion services.'],
            'X' => ['icon' => 'fa-brands fa-x-twitter', 'color' => '#000000', 'desc' => 'Increase your authority on X with real promotion services and engagement.'],
            'Telegram' => ['icon' => 'fa-brands fa-telegram', 'color' => '#0088cc', 'desc' => 'Boost your Telegram channel members and post views with our promotion services.'],
            'Spotify' => ['icon' => 'fa-brands fa-spotify', 'color' => '#1DB954', 'desc' => 'Get your music heard with real Spotify promotion services, plays and followers.'],
            'Audiomack' => ['icon' => 'fa-solid fa-music', 'color' => '#FFA200', 'desc' => 'Climb the Audiomack charts with premium promotion services, plays and re-ups.'],
        ][$name] ?? ['icon' => 'fa-solid fa-share-nodes', 'color' => '#6366f1', 'desc' => 'Professional social media growth for ' . $name];

        return view('platform', compact('name', 'services', 'meta'));
    }

    public function instagramFollowers()
    {
        $data = [
            'name' => 'Buy Instagram Followers',
            'keyword' => 'Buy Instagram Organic Followers',
            'title' => 'Buy Instagram Followers Real Accounts In Minutes',
            'description' => 'Boost your Instagram presence with organic-looking followers. Fast delivery, high retention, and 24/7 support.',
            'icon' => 'fa-solid fa-users',
            'color' => '#d63384',
            'why_matter' => [
                'title' => 'Why Instagram Followers Matter',
                'desc' => 'In the digital age, your follower count is your primary social currency. It is the first thing people notice and the primary metric Instagram\'s algorithm uses to judge your worthiness for the Explore page. A strong following doesn\'t just look good—it builds an immediate "Aura of Authority" that makes organic users more likely to trust your content and follow you naturally.',
                'points' => [
                    ['title' => 'Digital Social Proof', 'desc' => 'People follow what others are already following. It\'s human nature.'],
                    ['title' => 'Algorithm Acceleration', 'desc' => 'Tell the AI that your content is worth promoting to millions.'],
                    ['title' => 'Monetization Potential', 'desc' => 'Higher numbers unlock brand deals, affiliate links, and shop features.']
                ]
            ],
            'audience' => [
                [
                    'name' => 'Individuals',
                    'desc' => 'Whether you are an artist, a photographer, or a passionate hobbyist, you deserve an audience. Buying followers helps you break out of the "zero-growth loop" and ensures that the content you spend hours creating actually gets seen by the world.',
                    'icon' => 'fa-solid fa-palette'
                ],
                [
                    'name' => 'Businesses',
                    'desc' => 'For brands, a follower count is a trust metric. Potential customers judge your reliability based on your social community. Establishing a strong base helps you convert visitors into paying customers by showing that you are an established player in your market.',
                    'icon' => 'fa-solid fa-chart-simple'
                ],
                [
                    'name' => 'Influencers',
                    'desc' => 'In the world of professional influencers, momentum is everything. To secure high-value brand partnerships, you need to show consistent growth and a massive reach. Our services provide the "Booster Rocket" needed to maintain your status as a leader.',
                    'icon' => 'fa-solid fa-camera-retro'
                ]
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-user-check', 'features' => ['High Quality Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Followers', '30-Day Refill Guarantee', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Celebrity VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Targeted Global People', 'Explore Page Reach', 'Instant Delivery Start', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Instant Social Proof', 'desc' => 'A higher follower count instantly signals authority and credibility to anyone who visits your profile.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Algorithm Advantage', 'desc' => 'Instagram\'s algorithm favors growing accounts, pushing your content to the Explore page for more organic reach.', 'icon' => 'fa-solid fa-chart-line'],
                ['title' => 'Brand Authority', 'desc' => 'Build trust with potential customers and partners by showcasing a strong, well-established social presence.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Rapid Growth', 'desc' => 'Skip the months of slow organic growth and get the momentum you need to go viral in minutes.', 'icon' => 'fa-solid fa-rocket']
            ],
            'faqs' => [
                ['q' => 'Is it safe to buy Instagram followers?', 'a' => 'Yes, Linkocial uses 100% organic-looking growth methods that comply with Instagram\'s safety standards. We never ask for your password.'],
                ['q' => 'How long does delivery take?', 'a' => 'Our systems start processing your order within 60 seconds. Depending on the package size, delivery is completed gradually to ensure safety.'],
                ['q' => 'Do the followers drop over time?', 'a' => 'We provide high-retention followers. If any drops occur, our Active and VIP packages include a 30-day refill guarantee.'],
                ['q' => 'Will anyone know I bought followers?', 'a' => 'No. We provide high-quality profiles with profile pictures and bios that look natural and integrate seamlessly with your existing audience.']
            ]
        ];

        return $this->renderService($data);
    }

    public function instagramLikes()
    {
        $data = [
            'name' => 'Buy Instagram Likes',
            'keyword' => 'Buy Instagram Real Likes',
            'title' => 'Buy Instagram Likes | Fast & Organic Growth Booster',
            'description' => 'Increase your Instagram post reach with high-quality likes. Boost engagement and trend on the explore page instantly.',
            'icon' => 'fa-solid fa-heart',
            'color' => '#d63384',
            'why_matter' => [
                'title' => 'Why Instagram Likes Matter',
                'desc' => 'On Instagram, likes are more than just a vanity metric—they are the primary signal to the algorithm that your content is high-value. When a post gets a sudden burst of likes, it triggers a chain reaction that pushes your content to the top of your followers\' feeds and onto the global Explore page.',
                'points' => [
                    ['title' => 'Algorithm Dominance', 'desc' => 'High engagement velocity tells Instagram to promote your post.'],
                    ['title' => 'Instant Validation', 'desc' => 'Users are 70% more likely to interact with a post that already has likes.'],
                    ['title' => 'Viral Potential', 'desc' => 'Likes are the fuel that powers your journey to the Explore page.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your hard work gets the recognition it deserves by boosting post visibility immediately.', 'icon' => 'fa-solid fa-wand-magic-sparkles'],
                ['name' => 'Businesses', 'desc' => 'Build trust in your products by showcasing high customer engagement on your promotional posts.', 'icon' => 'fa-solid fa-bag-shopping'],
                ['name' => 'Influencers', 'desc' => 'Maintain your status as a trendsetter by ensuring every post reflects your massive global reach.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-thumbs-up', 'features' => ['High Quality Likes', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Likes', 'Algorithm Boosting', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Viral VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Explore Page Placement', 'Impression + Reach Boost', 'Instant Delivery Start', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Explore Page Reach', 'desc' => 'High like counts signal to Instagram that your content is viral, increasing your chances of explore page placement.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Social Validation', 'desc' => 'Posts with thousands of likes naturally attract more organic engagement and trust from new viewers.', 'icon' => 'fa-solid fa-check-to-slot'],
                ['title' => 'Faster Ranking', 'desc' => 'Boost your post in your followers\' feeds and under relevant hashtags for maximum visibility.', 'icon' => 'fa-solid fa-arrow-up-right-dots'],
                ['title' => 'Brand Growth', 'desc' => 'Consistent high engagement builds a professional image that attracts sponsorships and partnerships.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Will buying likes help me get on the Explore page?', 'a' => 'Yes! Instagram\'s algorithm uses engagement velocity to determine what content is trending. Our VIP likes are designed for this.'],
                ['q' => 'Can I split the likes across multiple posts?', 'a' => 'Our dashboard allows you to select specific posts for delivery. For automatic likes on future posts, check our Subscription section.'],
                ['q' => 'Are these real likes?', 'a' => 'We provide high-quality engagement from accounts that look natural and active, ensuring your growth looks 100% organic.'],
                ['q' => 'Do you need my login details?', 'a' => 'Never. We only need the public link to your Instagram post to start the delivery process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function instagramComments()
    {
        $data = [
            'name' => 'Buy Instagram Comments',
            'keyword' => 'Buy Real Comments Instagram',
            'title' => 'Buy Instagram Comments | Get Authentic Social Engagement',
            'description' => 'Drive meaningful conversations on your Instagram posts. Premium, relevant comments from real-looking profiles.',
            'icon' => 'fa-solid fa-comments',
            'color' => '#d63384',
            'why_matter' => [
                'title' => 'Why Instagram Comments Matter',
                'desc' => 'While likes are great, comments are the "High-Intent" metric that the Instagram algorithm values most. A post with an active comment section tells the platform that your content is worth starting a conversation over. This creates a community atmosphere that attracts real, organic followers at a much faster rate.',
                'points' => [
                    ['title' => 'Maximum Authority', 'desc' => 'Detailed comments show that your audience is truly invested in you.'],
                    ['title' => 'SEO Optimization', 'desc' => 'Comments help your posts appear in Instagram search for specific keywords.'],
                    ['title' => 'Community Building', 'desc' => 'Encourage your real fans to join the discussion and stay engaged.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Break the "Silence Barrier" by starting the conversation on your new posts immediately.', 'icon' => 'fa-solid fa-microphone-lines'],
                ['name' => 'Businesses', 'desc' => 'Build a "Busy Shop" atmosphere by having active discussions about your products and services.', 'icon' => 'fa-solid fa-store'],
                ['name' => 'Influencers', 'desc' => 'Show potential brand sponsors that your audience is highly engaged and responsive to your content.', 'icon' => 'fa-solid fa-user-group']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-comment-dots', 'features' => ['High Quality Comments', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Custom Premium', 'icon' => 'fa-solid fa-pen-to-square', 'features' => ['You Choose the Text', '30-Day Refill Guarantee', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Influencer VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Relevant Long Comments', 'Impression Boost', 'Instant Delivery Start', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Deep Engagement', 'desc' => 'Comments are the most valuable metric for the algorithm, signaling "High Intent" content to the platform.', 'icon' => 'fa-solid fa-comments-dollar'],
                ['title' => 'Conversation Starter', 'desc' => 'Existing comments encourage your real followers to join the conversation and voice their opinions.', 'icon' => 'fa-solid fa-users-rays'],
                ['title' => 'Keyword SEO', 'desc' => 'Custom comments allow you to inject relevant keywords into your post section, helping with Instagram Search.', 'icon' => 'fa-solid fa-magnifying-glass'],
                ['title' => 'Build Trust', 'desc' => 'A post with active discussion looks more authentic and authoritative than a post with only likes.', 'icon' => 'fa-solid fa-handshake-angle']
            ],
            'faqs' => [
                ['q' => 'Can I write my own comments?', 'a' => 'Yes! Our Premium tier allows you to provide custom text so the comments perfectly match your post\'s context.'],
                ['q' => 'Are the comments relevant to my post?', 'a' => 'For our VIP tier, we ensure comments are contextually appropriate. For standard tiers, we use generic positive engagement.'],
                ['q' => 'Is it safe for my account?', 'a' => 'Absolutely. We deliver comments at a natural pace from established profiles, keeping your account 100% safe.'],
                ['q' => 'When will the comments appear?', 'a' => 'Orders typically start within minutes. You\'ll see a steady flow of engagement appearing on your post shortly after.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- TIKTOK SEO PAGES --- */

    public function tiktokFollowers()
    {
        $data = [
            'name' => 'Buy TikTok Followers',
            'keyword' => 'Buy TikTok Followers Real',
            'title' => 'Buy TikTok Followers | Get Real Engagement & Viral Growth',
            'description' => 'Explode your TikTok presence with real, high-quality followers. Fast delivery, safe growth, and 24/7 support.',
            'icon' => 'fa-brands fa-tiktok',
            'color' => '#000000',
            'why_matter' => [
                'title' => 'Why TikTok Followers Matter',
                'desc' => 'TikTok is the fastest-growing platform in the world, and your follower count is the primary signal of your viral potential. A strong following tells the TikTok algorithm that your content is worth pushing to the "For You Page" (FYP), creating a massive snowball effect of organic reach and visibility.',
                'points' => [
                    ['title' => 'FYP Algorithm Trigger', 'desc' => 'Accounts with more followers get higher initial priority on the FYP.'],
                    ['title' => 'Instant Authority', 'desc' => 'Users are much more likely to follow a creator who already has a community.'],
                    ['title' => 'Creator Fund Access', 'desc' => 'Reach the 10k follower milestone faster to unlock monetization.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Break out of the "zero-view" loop and get the visibility your creative videos deserve.', 'icon' => 'fa-solid fa-wand-magic-sparkles'],
                ['name' => 'Businesses', 'desc' => 'Build a modern brand image that resonates with the TikTok generation and drives sales.', 'icon' => 'fa-solid fa-shop'],
                ['name' => 'Influencers', 'desc' => 'Scale your reach and secure high-paying brand deals by showing consistent growth momentum.', 'icon' => 'fa-solid fa-camera-retro']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Retention Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Users', 'FYP Acceleration', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Viral VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Explore Page Reach', 'Priority Delivery', 'Instant Start', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Viral Momentum', 'desc' => 'Build the initial social proof needed to trigger TikTok\'s "For You Page" algorithm.', 'icon' => 'fa-solid fa-rocket'],
                ['title' => 'Social Credibility', 'desc' => 'A large follower base makes your account look established and professional to new viewers.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Unlock Features', 'desc' => 'Reach the required follower milestones to unlock TikTok LIVE and the Creator Fund.', 'icon' => 'fa-solid fa-key'],
                ['title' => 'Faster Growth', 'desc' => 'Our followers help attract organic users by showing that your content is worth following.', 'icon' => 'fa-solid fa-chart-line']
            ],
            'faqs' => [
                ['q' => 'Is it safe for my TikTok account?', 'a' => 'Absolutely. We use gradual delivery methods that mimic organic growth to keep your account 100% safe.'],
                ['q' => 'Will I get on the FYP?', 'a' => 'A strong follower count is a key signal for the algorithm. While content is king, our services provide the boost needed to get seen.'],
                ['q' => 'How fast is delivery?', 'a' => 'Most orders begin within minutes. We deliver at a natural pace to ensure the highest quality and safety.'],
                ['q' => 'Do you need my password?', 'a' => 'Never. We only need your TikTok username to start the growth process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function tiktokLikes()
    {
        $data = [
            'name' => 'Buy TikTok Likes',
            'keyword' => 'Buy Real TikTok Likes',
            'title' => 'Buy TikTok Likes | Boost Your FYP Presence Instantly',
            'description' => 'Increase your TikTok post engagement with real likes. High quality, instant delivery, and safe growth.',
            'icon' => 'fa-solid fa-heart',
            'color' => '#ff0050',
            'why_matter' => [
                'title' => 'Why TikTok Likes Matter',
                'desc' => 'Likes are the "Engagement Currency" of TikTok. The algorithm uses like-to-view ratios to determine if a video should go viral. When you buy likes, you are telling TikTok that your content is high-value, causing the platform to push it to more people and increase your overall reach.',
                'points' => [
                    ['title' => 'Viral Velocity', 'desc' => 'High like counts in the first hour can trigger massive FYP reach.'],
                    ['title' => 'Content Validation', 'desc' => 'Users are much more likely to watch a video that already has thousands of likes.'],
                    ['title' => 'Algorithm Priority', 'desc' => 'The TikTok AI favors content with high engagement-to-impression ratios.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Get your content seen by millions by boosting your engagement ratios immediately.', 'icon' => 'fa-solid fa-user-check'],
                ['name' => 'Businesses', 'desc' => 'Create a "Busy Shop" feel on your promotional videos to drive trust and conversions.', 'icon' => 'fa-solid fa-bag-shopping'],
                ['name' => 'Influencers', 'desc' => 'Maintain your viral status and show brand partners that your audience is highly engaged.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-thumbs-up', 'features' => ['High Quality Likes', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Viral Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Likes', 'Algorithm Trigger', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'FYP VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Impression + Reach Boost', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'FYP Exposure', 'desc' => 'Boost your likes to signal to the algorithm that your content is worth promoting to a global audience.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Social Validation', 'desc' => 'People are naturally drawn to popular content. High like counts attract more organic engagement.', 'icon' => 'fa-solid fa-check-to-slot'],
                ['title' => 'Instant Impact', 'desc' => 'See results in minutes and watch your video gain momentum almost immediately.', 'icon' => 'fa-solid fa-clock-rotate-left'],
                ['title' => 'Brand Growth', 'desc' => 'Consistent engagement builds a professional image that attracts high-value collaborations.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Can I split the likes?', 'a' => 'Yes! You can choose which videos receive the likes during the checkout process on our dashboard.'],
                ['q' => 'Are these real likes?', 'a' => 'We provide high-quality engagement that looks natural and adheres to TikTok\'s safety guidelines.'],
                ['q' => 'How soon will I see results?', 'a' => 'Orders usually start within minutes, and you\'ll see a steady increase in engagement shortly after.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe, established delivery methods to ensure your account is never at risk.']
            ]
        ];

        return $this->renderService($data);
    }

    public function tiktokViews()
    {
        $data = [
            'name' => 'Buy TikTok Views',
            'keyword' => 'Buy High Quality TikTok Views',
            'title' => 'Buy High Quality TikTok Views',
            'description' => 'Get instant momentum for your TikTok videos. High retention views, fast delivery, and real growth.',
            'icon' => 'fa-solid fa-play',
            'color' => '#00f2ea',
            'why_matter' => [
                'title' => 'Why TikTok Views Matter',
                'desc' => 'In the world of TikTok, views are the first point of entry. A video with 100k views looks like a viral hit before a user even watches it. This initial "Viral Momentum" is crucial for convincing the algorithm that your content is worth showing to more people on the FYP.',
                'points' => [
                    ['title' => 'Viral Perception', 'desc' => 'High view counts immediately signal to users that your content is "Must-Watch".'],
                    ['title' => 'Retention Boosting', 'desc' => 'Tell the algorithm that users are watching your content to completion.'],
                    ['title' => 'Search Dominance', 'desc' => 'Higher views help your videos rank for relevant TikTok search keywords.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your creative work gets the "Viral Start" it needs to go global.', 'icon' => 'fa-solid fa-palette'],
                ['name' => 'Businesses', 'desc' => 'Boost your product demos and promotional videos to reach a massive potential customer base.', 'icon' => 'fa-solid fa-chart-simple'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach and show brand partners that your content has massive global appeal.', 'icon' => 'fa-solid fa-user-group']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Views', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'High Retention', 'icon' => 'fa-solid fa-bolt', 'features' => ['Long Watch Time', 'Algorithm Trigger', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Viral VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Impression + Reach Boost', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Viral Start', 'desc' => 'Give your new videos the initial push they need to trigger the TikTok viral algorithm.', 'icon' => 'fa-solid fa-bolt-lightning'],
                ['title' => 'Search Ranking', 'desc' => 'Videos with higher view counts rank higher in TikTok search results for relevant hashtags.', 'icon' => 'fa-solid fa-magnifying-glass'],
                ['title' => 'Social Trust', 'desc' => 'Users are much more likely to stop and watch a video that already has a massive view count.', 'icon' => 'fa-solid fa-handshake-angle'],
                ['title' => 'Account Growth', 'desc' => 'Consistent views lead to more likes and followers, accelerating your overall channel growth.', 'icon' => 'fa-solid fa-arrow-up-right-dots']
            ],
            'faqs' => [
                ['q' => 'Will views help me go viral?', 'a' => 'Yes! High view counts in the first few hours are a major signal for the TikTok algorithm to push your content to the FYP.'],
                ['q' => 'Are these high-retention views?', 'a' => 'Our Premium and VIP tiers focus on high-retention views to ensure the best possible results for the algorithm.'],
                ['q' => 'Can I split views?', 'a' => 'Yes, you can distribute views across multiple videos in a single order via our dashboard.'],
                ['q' => 'Is my account safe?', 'a' => 'Absolutely. We use safe, organic-style delivery methods that fully comply with TikTok\'s standards.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- YOUTUBE SEO PAGES --- */

    public function youtubeSubscribers()
    {
        $data = [
            'name' => 'Buy YouTube Subscribers',
            'keyword' => 'Buy YouTube Subscribers Real',
            'title' => 'Buy YouTube Subscribers | Build Your Channel Foundation',
            'description' => 'Grow your YouTube channel with real subscribers. Build authority, unlock features, and boost your search ranking.',
            'icon' => 'fa-brands fa-youtube',
            'color' => '#FF0000',
            'why_matter' => [
                'title' => 'Why YouTube Subscribers Matter',
                'desc' => 'Subscribers are the "Foundation" of any successful YouTube channel. They are your loyal audience who get notified of every new release. In addition to social proof, reaching subscriber milestones (like 1,000) is a critical requirement for YouTube Partner Program (monetization) and channel authority.',
                'points' => [
                    ['title' => 'Monetization Milestone', 'desc' => 'Reach the 1,000 subscriber goal faster to start earning revenue.'],
                    ['title' => 'Search Ranking SEO', 'desc' => 'Channels with more subscribers rank higher in YouTube search results.'],
                    ['title' => 'Channel Authority', 'desc' => 'Build immediate trust with new viewers and potential brand sponsors.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Start your journey to becoming a professional YouTuber by building a solid subscriber base.', 'icon' => 'fa-solid fa-microphone'],
                ['name' => 'Businesses', 'desc' => 'Establish your brand as a market leader by building a large community around your video content.', 'icon' => 'fa-solid fa-briefcase'],
                ['name' => 'Influencers', 'desc' => 'Unlock high-value brand deals and sponsorships by showing massive channel authority and reach.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-user-check', 'features' => ['High Quality Subs', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Profiles', '30-Day Refill Guarantee', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Partner VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Monetization Boost', 'Natural Growth Pace', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Unlock Revenue', 'desc' => 'Reach the required subscriber counts to join the YouTube Partner Program and start earning ad revenue.', 'icon' => 'fa-solid fa-sack-dollar'],
                ['title' => 'Search Authority', 'desc' => 'YouTube\'s search engine favors channels with larger subscriber bases, pushing your videos to the top of search.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Brand Trust', 'desc' => 'A high subscriber count makes your channel look professional and authoritative to potential viewers.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Organic Growth', 'desc' => 'Subscribers attract more organic viewers by showing that your content is high-value and worth following.', 'icon' => 'fa-solid fa-chart-line']
            ],
            'faqs' => [
                ['q' => 'Will these subscribers help with monetization?', 'a' => 'Yes! These are real-looking, high-quality subscribers that count toward your monetization milestones.'],
                ['q' => 'Is it safe for my channel?', 'a' => 'Absolutely. We deliver subscribers at a natural, safe pace to ensure your channel remains in good standing.'],
                ['q' => 'How fast do they arrive?', 'a' => 'We use a "Drip-Feed" method for YouTube to ensure the most natural growth possible. You will see a steady increase daily.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need your Channel URL to start the delivery process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function youtubeViews()
    {
        $data = [
            'name' => 'Buy YouTube Views',
            'keyword' => 'Buy YouTube Views High Retention',
            'title' => 'Buy YouTube Views | High Retention Video Growth',
            'description' => 'Get real views for your YouTube videos. Increase watch time, boost search rankings, and go viral.',
            'icon' => 'fa-solid fa-play',
            'color' => '#FF0000',
            'why_matter' => [
                'title' => 'Why YouTube Views Matter',
                'desc' => 'On YouTube, views are the "Social Proof" that your content is worth watching. High view counts tell the YouTube search and recommendation algorithm that your video is high-quality, causing it to rank higher and be suggested to more organic viewers globally.',
                'points' => [
                    ['title' => 'Search Ranking SEO', 'desc' => 'Videos with higher views rank much higher in YouTube search results.'],
                    ['title' => 'Watch Time Boost', 'desc' => 'High-retention views increase your total watch time, a key monetization metric.'],
                    ['title' => 'Social Validation', 'desc' => 'Users are 3x more likely to click on a video that already has thousands of views.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your hard work gets the recognition it deserves by reaching a massive global audience.', 'icon' => 'fa-solid fa-user-check'],
                ['name' => 'Businesses', 'desc' => 'Boost your promotional videos to reach potential customers and build brand authority.', 'icon' => 'fa-solid fa-chart-line'],
                ['name' => 'Influencers', 'desc' => 'Show brand partners that your content has massive reach and engagement by boosting your view counts.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Views', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'High Retention', 'icon' => 'fa-solid fa-bolt', 'features' => ['3-5 Min Watch Time', 'Algorithm Trigger', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Viral VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Search Ranking Boost', 'Keyword Targeted', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Search Visibility', 'desc' => 'YouTube search favors videos with higher view counts, helping you rank for competitive keywords.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Monetization Reach', 'desc' => 'Build the required watch time hours needed to join the YouTube Partner Program.', 'icon' => 'fa-solid fa-dollar-sign'],
                ['title' => 'Global Appeal', 'desc' => 'A high view count makes your content look globally relevant and attractive to new viewers.', 'icon' => 'fa-solid fa-globe'],
                ['title' => 'Algorithm Suggestion', 'desc' => 'High-quality views trigger YouTube to suggest your video to more people in their "Recommended" feed.', 'icon' => 'fa-solid fa-up-right-from-square']
            ],
            'faqs' => [
                ['q' => 'Are these high-retention views?', 'a' => 'Yes! We focus on high-retention views to ensure they provide the maximum benefit to your search ranking and monetization.'],
                ['q' => 'Can I split the views?', 'a' => 'Yes, you can distribute views across multiple videos in your channel via our dashboard.'],
                ['q' => 'Is it safe for monetization?', 'a' => 'Our views are delivered via safe, external methods that comply with YouTube\'s policies, keeping your monetization safe.'],
                ['q' => 'When will I see the results?', 'a' => 'YouTube usually updates view counts every 24-48 hours. You will see the delivery progress on our dashboard immediately.']
            ]
        ];

        return $this->renderService($data);
    }

    public function youtubeWatchTime()
    {
        $data = [
            'name' => 'Buy YouTube Watch Time',
            'keyword' => 'Buy 4000 Hours Watch Time YouTube',
            'title' => 'Buy YouTube Watch Time | Fast Track To Monetization',
            'description' => 'Reach the 4,000 hour requirement faster. High-retention watch time to unlock YouTube monetization and growth.',
            'icon' => 'fa-solid fa-clock',
            'color' => '#FF0000',
            'why_matter' => [
                'title' => 'Why YouTube Watch Time Matter',
                'desc' => 'Watch time is the single most important metric for YouTube monetization. To join the Partner Program, you need 4,000 hours of public watch time. Our specialized service provides high-retention views that focus specifically on increasing this metric, helping you unlock revenue faster than ever before.',
                'points' => [
                    ['title' => 'Monetization Unlocking', 'desc' => 'Reach the 4,000-hour requirement to start earning from your content.'],
                    ['title' => 'Channel Ranking Boost', 'desc' => 'YouTube prioritizes channels that keep users on the platform longer.'],
                    ['title' => 'Revenue Potential', 'desc' => 'Unlocking monetization is the first step to building a profitable channel.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Fast-track your journey to professional YouTube monetization by hitting your hour goals.', 'icon' => 'fa-solid fa-hourglass-start'],
                ['name' => 'Businesses', 'desc' => 'Build a high-authority channel that ranks at the top of your industry search results.', 'icon' => 'fa-solid fa-briefcase'],
                ['name' => 'Influencers', 'desc' => 'Maximize your channel revenue and authority by ensuring high watch time metrics.', 'icon' => 'fa-solid fa-chart-line']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Hours', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Monetization Pro', 'icon' => 'fa-solid fa-bolt', 'features' => ['High Retention Focus', 'Monetization Safe', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'VIP Freedom', 'icon' => 'fa-solid fa-crown', 'features' => ['Keyword Targeted', 'Custom Delivery Pace', 'Instant Start', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Earn Revenue Faster', 'desc' => 'Reach the required 4,000 hours of watch time to join the YouTube Partner Program immediately.', 'icon' => 'fa-solid fa-money-bill-trend-up'],
                ['title' => 'Algorithm Dominance', 'desc' => 'YouTube\'s AI rewards channels with high watch time by promoting them to more users.', 'icon' => 'fa-solid fa-arrow-up-wide-short'],
                ['title' => 'Search Authority', 'desc' => 'Higher watch time metrics lead to higher rankings for your videos in YouTube search results.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Channel Growth', 'desc' => 'Unlock monetization and channel features that are only available to established creators.', 'icon' => 'fa-solid fa-unlock-keyhole']
            ],
            'faqs' => [
                ['q' => 'Do these hours count for monetization?', 'a' => 'Yes! We use high-retention views from real-looking accounts that count toward the 4,000-hour public watch time requirement.'],
                ['q' => 'How long of a video do I need?', 'a' => 'For the best results, we recommend using videos that are at least 15-60 minutes long.'],
                ['q' => 'Is it safe for my channel?', 'a' => 'Absolutely. We use safe, natural delivery methods that mimic organic viewer behavior to keep your channel safe.'],
                ['q' => 'When will I reach the 4,000 hours?', 'a' => 'Depending on the package size, we deliver at a safe pace to ensure the best results. Most goals are reached within 2-4 weeks.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- X (TWITTER) SEO PAGES --- */

    public function xFollowers()
    {
        $data = [
            'name' => 'Buy X Followers',
            'keyword' => 'Buy X Followers Real',
            'title' => 'Buy X Followers | Increase Your Authority on X',
            'description' => 'Build your presence on X with high-quality followers. Fast delivery, real accounts, and 24/7 support.',
            'icon' => 'fa-brands fa-x-twitter',
            'color' => '#000000',
            'why_matter' => [
                'title' => 'Why X Followers Matter',
                'desc' => 'On X, your follower count is your primary trust metric. In the "Public Square" of the internet, authority is determined by reach. A strong follower base ensures your threads get seen, your opinions carry weight, and your brand is seen as a leader in its industry.',
                'points' => [
                    ['title' => 'Public Authority', 'desc' => 'Reach determined by the size of your established community.'],
                    ['title' => 'Viral Potential', 'desc' => 'The algorithm prioritizes accounts with high social proof.'],
                    ['title' => 'Brand Credibility', 'desc' => 'Professional brands need a strong follower base to build trust.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your thoughts and opinions reach a wider audience in the public square.', 'icon' => 'fa-solid fa-bullhorn'],
                ['name' => 'Businesses', 'desc' => 'Build a professional brand image that attracts customers and partners on X.', 'icon' => 'fa-solid fa-briefcase'],
                ['name' => 'Influencers', 'desc' => 'Maintain your status as a thought leader and secure high-value partnerships.', 'icon' => 'fa-solid fa-crown']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Profiles', 'Algorithm Booster', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Authority VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Global High Impact', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Instant Authority', 'desc' => 'A high follower count instantly signals credibility and reach to anyone who visits your profile.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Algorithm Advantage', 'desc' => 'The X algorithm favors accounts with growing communities, increasing your thread visibility.', 'icon' => 'fa-solid fa-chart-line'],
                ['title' => 'Viral Reach', 'desc' => 'Building a solid foundation helps your content gain organic traction faster through reposts.', 'icon' => 'fa-solid fa-share-nodes'],
                ['title' => 'Professional Image', 'desc' => 'Show potential partners that you are an established and influential player in your field.', 'icon' => 'fa-solid fa-shield-halved']
            ],
            'faqs' => [
                ['q' => 'Is it safe for my X account?', 'a' => 'Yes. We use safe, organic-looking growth methods that comply with X\'s security standards.'],
                ['q' => 'Will anyone know I bought followers?', 'a' => 'No. We provide high-quality profiles that look natural and integrate seamlessly with your audience.'],
                ['q' => 'How fast is delivery?', 'a' => 'Orders typically start within minutes. We deliver at a safe, steady pace for the best results.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need your X username to start the growth process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function xLikes()
    {
        $data = [
            'name' => 'Buy X Likes',
            'keyword' => 'Buy Real X Likes',
            'title' => 'Buy X Likes | Boost Your Thread Engagement',
            'description' => 'Increase your reach on X with real post likes. High quality, instant delivery, and safe growth.',
            'icon' => 'fa-solid fa-heart',
            'color' => '#000000',
            'why_matter' => [
                'title' => 'Why X Likes Matter',
                'desc' => 'Likes are the primary engagement metric on X. They tell the algorithm that your content is valuable, causing it to be pushed into the feeds of people who don\'t even follow you yet. High like counts are the fuel that powers viral threads.',
                'points' => [
                    ['title' => 'Algorithm Trigger', 'desc' => 'The X AI promotes content with high initial engagement.'],
                    ['title' => 'Social Proof', 'desc' => 'People are 80% more likely to read a thread that already has likes.'],
                    ['title' => 'Visibility Boost', 'desc' => 'Likes increase your chances of appearing in the "For You" feed.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your viral threads get the "Initial Spark" needed to reach millions.', 'icon' => 'fa-solid fa-bolt'],
                ['name' => 'Businesses', 'desc' => 'Build trust in your product announcements and company updates.', 'icon' => 'fa-solid fa-shop'],
                ['name' => 'Influencers', 'desc' => 'Maintain your high-engagement image and attract new organic followers.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-thumbs-up', 'features' => ['High Quality Likes', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Viral Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Likes', 'Algorithm Velocity', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Thread VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Impression + Reach Boost', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Viral Momentum', 'desc' => 'Give your threads the initial push they need to trigger the X recommendation engine.', 'icon' => 'fa-solid fa-rocket'],
                ['title' => 'Public Trust', 'desc' => 'Content with thousands of likes naturally attracts more organic engagement and trust.', 'icon' => 'fa-solid fa-check-to-slot'],
                ['title' => 'Thread Reach', 'desc' => 'Boost your posts into the feeds of relevant users to maximize your organic reach.', 'icon' => 'fa-solid fa-arrow-up-right-dots'],
                ['title' => 'Brand Growth', 'desc' => 'Consistent engagement builds a professional image that attracts sponsorships.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Can I split likes?', 'a' => 'Yes! You can distribute likes across multiple posts during the checkout process.'],
                ['q' => 'Are these real likes?', 'a' => 'We provide high-quality engagement from active-looking accounts to ensure safe growth.'],
                ['q' => 'How fast do they arrive?', 'a' => 'Most orders begin within minutes of payment, providing immediate traction.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe delivery methods that fully comply with X\'s guidelines.']
            ]
        ];

        return $this->renderService($data);
    }

    public function xReposts()
    {
        $data = [
            'name' => 'Buy X Reposts',
            'keyword' => 'Buy High Quality X Reposts',
            'title' => 'Buy X Reposts | Explode Your Content Reach',
            'description' => 'Get your content shared across X instantly. High retention reposts, fast delivery, and real growth.',
            'icon' => 'fa-solid fa-share-nodes',
            'color' => '#000000',
            'why_matter' => [
                'title' => 'Why X Reposts Matter',
                'desc' => 'Reposts are the ultimate viral signal on X. They take your content out of your own circle and put it directly into the feeds of entire new audiences. A video or thread with thousands of reposts is the fastest way to achieve global visibility on the platform.',
                'points' => [
                    ['title' => 'Network Effect', 'desc' => 'Each repost exposes your content to an entirely new group of users.'],
                    ['title' => 'Maximum Visibility', 'desc' => 'The X algorithm values shares above all other engagement metrics.'],
                    ['title' => 'Viral Potential', 'desc' => 'Reposts are the primary engine for organic, high-speed viral growth.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Get your message heard by millions by leveraging the power of X\'s network.', 'icon' => 'fa-solid fa-microphone-lines'],
                ['name' => 'Businesses', 'desc' => 'Spread your brand message and product launches across the entire platform.', 'icon' => 'fa-solid fa-globe'],
                ['name' => 'Influencers', 'desc' => 'Show potential partners that your content has massive, shareable appeal.', 'icon' => 'fa-solid fa-share-nodes']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-repeat', 'features' => ['High Quality Reposts', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Viral Network', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Shares', 'Algorithm Booster', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Global VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Reach Injection', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Global Exposure', 'desc' => 'Reposts carry your content to every corner of X, reaching users far beyond your followers.', 'icon' => 'fa-solid fa-earth-americas'],
                ['title' => 'Algorithm Dominance', 'desc' => 'High repost velocity tells the X AI that your content is trending globally.', 'icon' => 'fa-solid fa-chart-area'],
                ['title' => 'Rapid Traction', 'desc' => 'See your content spread in real-time as our network begins sharing your posts.', 'icon' => 'fa-solid fa-bolt-lightning'],
                ['title' => 'Social Credibility', 'desc' => 'A post with massive shares looks authoritative and highly relevant to new viewers.', 'icon' => 'fa-solid fa-handshake-angle']
            ],
            'faqs' => [
                ['q' => 'Will this help me go viral?', 'a' => 'Yes! Reposts are the most powerful signal for viral growth on the X platform.'],
                ['q' => 'Are these real reposts?', 'a' => 'We provide high-quality engagement from profiles that look active and natural.'],
                ['q' => 'Can I choose which posts?', 'a' => 'Yes, you can provide the links to any public X post you want to boost.'],
                ['q' => 'How soon will I see shares?', 'a' => 'Orders typically start processing within minutes of order confirmation.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- FACEBOOK SEO PAGES --- */

    public function facebookPageLikes()
    {
        $data = [
            'name' => 'Buy Facebook Page Likes',
            'keyword' => 'Buy Facebook Page Likes Real',
            'title' => 'Buy Facebook Page Likes | Build Your Brand Authority',
            'description' => 'Establish your Facebook presence with high-quality page likes. Build trust, reach customers, and grow your brand.',
            'icon' => 'fa-brands fa-facebook',
            'color' => '#1877F2',
            'why_matter' => [
                'title' => 'Why Facebook Page Likes Matter',
                'desc' => 'For businesses, a Facebook page is your digital storefront. Page likes are the "Trust Badges" that tell potential customers you are an established and reliable brand. Higher like counts improve your page ranking in Facebook search and increase the trust of every visitor.',
                'points' => [
                    ['title' => 'Business Trust', 'desc' => 'High like counts act as immediate social proof for your brand.'],
                    ['title' => 'Search Visibility', 'desc' => 'Pages with more likes rank higher in Facebook search results.'],
                    ['title' => 'Organic Reach', 'desc' => 'Establishing a base helps your future posts reach more people naturally.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Build a professional public figure profile that attracts followers and fans.', 'icon' => 'fa-solid fa-user-tie'],
                ['name' => 'Businesses', 'desc' => 'Establish your brand as a market leader and build trust with new customers.', 'icon' => 'fa-solid fa-store'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach across all platforms by building a strong Facebook base.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-thumbs-up', 'features' => ['High Quality Likes', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Users', '30-Day Refill Guarantee', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Brand VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Impact Targeted', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Instant Trust', 'desc' => 'A high page like count tells potential customers that you are a reliable and established business.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Better SEO', 'desc' => 'Facebook\'s search algorithm favors pages with larger communities, helping you reach new clients.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Viral Potential', 'desc' => 'Building a base makes your future posts and ads more effective through social proof.', 'icon' => 'fa-solid fa-arrow-up-right-dots'],
                ['title' => 'Market Authority', 'desc' => 'Show your competitors and partners that you are a dominant force in your industry.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Are these real likes?', 'a' => 'We provide high-quality likes from profiles that look active and professional to ensure your page remains safe.'],
                ['q' => 'Is it safe for my page?', 'a' => 'Absolutely. We use safe delivery methods that fully comply with Facebook\'s community standards.'],
                ['q' => 'How long does it take?', 'a' => 'Orders typically start within minutes and are delivered at a safe, natural pace.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need the public link to your Facebook page to start the growth process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function facebookPostLikes()
    {
        $data = [
            'name' => 'Buy Facebook Post Likes',
            'keyword' => 'Buy Real Facebook Post Likes',
            'title' => 'Buy Facebook Post Likes | Boost Your Post Reach',
            'description' => 'Increase your Facebook post engagement instantly. High quality likes, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-heart',
            'color' => '#1877F2',
            'why_matter' => [
                'title' => 'Why Facebook Post Likes Matter',
                'desc' => 'Likes are the signal to Facebook\'s "Edgerank" algorithm that your content is high-value. When a post gets likes quickly, Facebook pushes it to the top of your followers\' news feeds and into the feeds of their friends, creating a massive organic reach boost.',
                'points' => [
                    ['title' => 'Algorithm Booster', 'desc' => 'High engagement tells Facebook to show your post to more people.'],
                    ['title' => 'Social Validation', 'desc' => 'Users are much more likely to interact with a post that already has likes.'],
                    ['title' => 'Ad Effectiveness', 'desc' => 'Boost your promotional posts with social proof to lower your ad costs.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your personal posts and photos get the recognition they deserve.', 'icon' => 'fa-solid fa-user-check'],
                ['name' => 'Businesses', 'desc' => 'Build trust in your product launches and special offers with high engagement.', 'icon' => 'fa-solid fa-bag-shopping'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach and show brand partners that your audience is highly active.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-thumbs-up', 'features' => ['High Quality Likes', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Viral Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Likes', 'Algorithm Velocity', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Reach VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Impact Reach', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'News Feed Priority', 'desc' => 'High-engagement posts are prioritized by Facebook\'s algorithm, reaching more of your audience.', 'icon' => 'fa-solid fa-list-check'],
                ['title' => 'Lower Ad Costs', 'desc' => 'Posts with high social proof perform better as ads, lowering your cost per engagement.', 'icon' => 'fa-solid fa-money-bill-trend-up'],
                ['title' => 'Brand Authority', 'desc' => 'A post with thousands of likes looks authoritative and highly relevant to new viewers.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Faster Growth', 'desc' => 'Boost your visibility and attract new followers by making your content trend.', 'icon' => 'fa-solid fa-arrow-up-right-dots']
            ],
            'faqs' => [
                ['q' => 'Can I split likes?', 'a' => 'Yes! You can distribute likes across multiple posts in a single order via our dashboard.'],
                ['q' => 'Are these real likes?', 'a' => 'We provide high-quality engagement from profiles that look active and natural.'],
                ['q' => 'How soon will I see results?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe, established delivery methods that fully comply with Facebook\'s standards.']
            ]
        ];

        return $this->renderService($data);
    }

    public function facebookFollowers()
    {
        $data = [
            'name' => 'Buy Facebook Followers',
            'keyword' => 'Buy Facebook Profile Followers',
            'title' => 'Buy Facebook Followers | Grow Your Personal Authority',
            'description' => 'Increase your Facebook profile followers instantly. High quality, real accounts, and safe growth.',
            'icon' => 'fa-solid fa-users',
            'color' => '#1877F2',
            'why_matter' => [
                'title' => 'Why Facebook Followers Matter',
                'desc' => 'For public figures and professionals, your profile follower count is your primary social currency. It signals authority, reach, and influence. A strong follower base ensures your updates reach a massive audience and builds immediate trust with anyone who follows you.',
                'points' => [
                    ['title' => 'Profile Authority', 'desc' => 'Reach more people beyond your friend circle with followers.'],
                    ['title' => 'Public Influence', 'desc' => 'Show your reach and impact as a thought leader or professional.'],
                    ['title' => 'Viral Capacity', 'desc' => 'Followers are the first people to see and share your new updates.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Build your personal brand and reach a wider audience outside your local circle.', 'icon' => 'fa-solid fa-user-group'],
                ['name' => 'Businesses', 'desc' => 'Build a personal connection with your customers through a high-authority profile.', 'icon' => 'fa-solid fa-handshake'],
                ['name' => 'Influencers', 'desc' => 'Maintain your status as a leader and build a truly multi-platform audience.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Users', 'Profile Authority', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Authority VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Global High Impact', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Maximum Reach', 'desc' => 'Build an audience that gets your updates directly in their news feed, reaching more people than ever.', 'icon' => 'fa-solid fa-tower-broadcast'],
                ['title' => 'Profile Trust', 'desc' => 'A high follower count instantly signals authority and credibility to anyone who visits your profile.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Organic Growth', 'desc' => 'Followers attract more organic users by showing that your content is worth following.', 'icon' => 'fa-solid fa-chart-line'],
                ['title' => 'Build Influence', 'desc' => 'Show potential partners and brand sponsors that you have a massive, engaged audience.', 'icon' => 'fa-solid fa-crown']
            ],
            'faqs' => [
                ['q' => 'Is it safe for my profile?', 'a' => 'Yes. We use safe, organic-style delivery methods that fully comply with Facebook\'s standards.'],
                ['q' => 'Will anyone know?', 'a' => 'No. We provide high-quality profiles that look natural and integrate seamlessly with your existing audience.'],
                ['q' => 'How fast is delivery?', 'a' => 'Orders typically start within minutes and are delivered at a safe, steady pace for best results.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need your public profile link to start the growth process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- TELEGRAM SEO PAGES --- */

    public function telegramMembers()
    {
        $data = [
            'name' => 'Buy Telegram Members',
            'keyword' => 'Buy Real Telegram Members',
            'title' => 'Buy Telegram Members | Build Your Channel Authority',
            'description' => 'Grow your Telegram channel or group instantly. High quality members, fast delivery, and safe growth.',
            'icon' => 'fa-brands fa-telegram',
            'color' => '#0088cc',
            'why_matter' => [
                'title' => 'Why Telegram Members Matter',
                'desc' => 'In the world of Telegram, the size of your group or channel is the first thing users look at. A massive member count signals legitimacy, authority, and trust. It tells potential members that your community is active and worth joining, creating an immediate social proof engine.',
                'points' => [
                    ['title' => 'Channel Legitimacy', 'desc' => 'New users are much more likely to join a large, active community.'],
                    ['title' => 'Search Authority', 'desc' => 'Larger channels rank higher in Telegram global search results.'],
                    ['title' => 'Broadcast Reach', 'desc' => 'Build a massive base for your news, signals, or product updates.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Start your community journey by building a strong member base immediately.', 'icon' => 'fa-solid fa-user-group'],
                ['name' => 'Businesses', 'desc' => 'Establish a professional brand presence and build trust with your customers.', 'icon' => 'fa-solid fa-store'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach and authority by building a massive Telegram following.', 'icon' => 'fa-solid fa-bullhorn']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Members', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Users', 'Search Visibility', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Community VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Authority Injection', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Instant Authority', 'desc' => 'A large member count instantly signals legitimacy and trust to anyone who finds your channel.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Telegram SEO', 'desc' => 'Higher member counts help your channel rank higher in Telegram\'s global search engine.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Rapid Traction', 'desc' => 'Building a solid foundation helps attract organic users by showing your community is popular.', 'icon' => 'fa-solid fa-rocket'],
                ['title' => 'Social Trust', 'desc' => 'Show potential partners and sponsors that you have a massive, established reach.', 'icon' => 'fa-solid fa-handshake-angle']
            ],
            'faqs' => [
                ['q' => 'Are these real members?', 'a' => 'We provide high-quality members from profiles that look active and natural to ensure safety.'],
                ['q' => 'Is it safe for my channel?', 'a' => 'Absolutely. We use safe delivery methods that fully comply with Telegram\'s standards.'],
                ['q' => 'When will I see results?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need the public link to your Telegram channel or group.']
            ]
        ];

        return $this->renderService($data);
    }

    public function telegramViews()
    {
        $data = [
            'name' => 'Buy Telegram Views',
            'keyword' => 'Buy Telegram Post Views',
            'title' => 'Buy Telegram Views | Boost Your Post Credibility',
            'description' => 'Increase your Telegram post views instantly. High quality, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-eye',
            'color' => '#0088cc',
            'why_matter' => [
                'title' => 'Why Telegram Views Matter',
                'desc' => 'On Telegram, views tell the story of your community\'s activity. High view counts on every post signal to new members that your content is being read and valued. It builds a "Busy Channel" atmosphere that keeps members engaged and attracts new ones.',
                'points' => [
                    ['title' => 'Active Atmosphere', 'desc' => 'Show your members that your community is highly active and engaged.'],
                    ['title' => 'Post Authority', 'desc' => 'Individual updates look more authoritative and credible with high view counts.'],
                    ['title' => 'Viral Signal', 'desc' => 'High views encourage members to share your posts with their own circles.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your updates and signals get the "Initial Traction" they deserve.', 'icon' => 'fa-solid fa-bolt'],
                ['name' => 'Businesses', 'desc' => 'Build trust in your product announcements and market updates.', 'icon' => 'fa-solid fa-chart-line'],
                ['name' => 'Influencers', 'desc' => 'Show brand partners that your audience is highly active and responsive.', 'icon' => 'fa-solid fa-user-check']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-eye', 'features' => ['High Quality Views', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Impression Signal', 'Algorithm Velocity', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Broadcast VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Authority Reach', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Build Trust', 'desc' => 'High view counts on your posts signal to new and existing members that your content is highly valued.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Active Image', 'desc' => 'Create a professional "Busy Channel" image that attracts more organic members and engagement.', 'icon' => 'fa-solid fa-users-viewfinder'],
                ['title' => 'Instant Impact', 'desc' => 'See results in minutes and watch your posts gain immediate social proof and authority.', 'icon' => 'fa-solid fa-bolt-lightning'],
                ['title' => 'Brand Growth', 'desc' => 'Consistent high views build a professional image that attracts high-value sponsorships.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Can I split views?', 'a' => 'Yes! You can distribute views across your most recent posts or select specific ones.'],
                ['q' => 'Are these real views?', 'a' => 'We provide high-quality engagement that looks natural and adheres to Telegram\'s safety standards.'],
                ['q' => 'How soon will results show?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe, established delivery methods to ensure your channel is never at risk.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- SPOTIFY SEO PAGES --- */

    public function spotifyFollowers()
    {
        $data = [
            'name' => 'Buy Spotify Followers',
            'keyword' => 'Buy Spotify Artist Followers',
            'title' => 'Buy Spotify Followers | Grow Your Artist Profile Authority',
            'description' => 'Build your presence on Spotify with high-quality artist followers. Fast delivery, real accounts, and 24/7 support.',
            'icon' => 'fa-brands fa-spotify',
            'color' => '#1DB954',
            'why_matter' => [
                'title' => 'Why Spotify Followers Matter',
                'desc' => 'On Spotify, your follower count is a primary metric for the algorithm. It signals to Spotify that users want to hear more from you, increasing your chances of being placed on the "Release Radar" and other personalized playlists. High followers build the "Artist Authority" needed to secure editorial placements.',
                'points' => [
                    ['title' => 'Playlist Eligibility', 'desc' => 'Higher follower counts increase your chances of editorial playlist placement.'],
                    ['title' => 'Release Radar Boost', 'desc' => 'Your new releases are automatically pushed to your followers\' personalized feeds.'],
                    ['title' => 'Artist Authority', 'desc' => 'Build a professional image that attracts labels and festival bookers.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Start your journey as an independent artist by building a solid fan foundation.', 'icon' => 'fa-solid fa-music'],
                ['name' => 'Businesses', 'desc' => 'Establish a professional presence for your label or music brand on Spotify.', 'icon' => 'fa-solid fa-record-vinyl'],
                ['name' => 'Influencers', 'desc' => 'Build your personal brand by showing massive reach as a public figure or curator.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Artist Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Profiles', 'Algorithm Booster', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Editorial VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Global High Impact', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Playlist Growth', 'desc' => 'Build the base needed to get noticed by Spotify\'s editorial team and playlist curators.', 'icon' => 'fa-solid fa-list-ul'],
                ['title' => 'Algorithm Trigger', 'desc' => 'Signal to the Spotify AI that your music is trending and deserves a spot on Release Radar.', 'icon' => 'fa-solid fa-chart-line'],
                ['title' => 'Social Proof', 'desc' => 'A high artist follower count instantly signals credibility and talent to new listeners.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Artist Legitimacy', 'desc' => 'Show labels and fans that you are an established and growing player in the music scene.', 'icon' => 'fa-solid fa-shield-halved']
            ],
            'faqs' => [
                ['q' => 'Are these real followers?', 'a' => 'We provide high-quality followers from profiles that look active and natural to ensure safety.'],
                ['q' => 'Is it safe for my artist account?', 'a' => 'Absolutely. We use safe, organic-style delivery methods that fully comply with Spotify\'s standards.'],
                ['q' => 'Will this help with playlists?', 'a' => 'Yes! Follower counts are a key factor in Spotify\'s algorithmic decisions for curated playlists.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need the link to your Spotify artist profile to start the process safely.']
            ]
        ];

        return $this->renderService($data);
    }

    public function spotifyPlays()
    {
        $data = [
            'name' => 'Buy Spotify Plays',
            'keyword' => 'Buy Real Spotify Plays',
            'title' => 'Buy Spotify Plays | Boost Your Track Popularity',
            'description' => 'Increase your Spotify track plays instantly. High quality, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-play',
            'color' => '#1DB954',
            'why_matter' => [
                'title' => 'Why Spotify Plays Matter',
                'desc' => 'Plays are the lifeblood of a track on Spotify. They determine your royalty potential, your chart position, and your visibility in search results. High play counts tell the Spotify algorithm that your track is trending, triggering massive organic reach through "Discover Weekly."',
                'points' => [
                    ['title' => 'Royalty Potential', 'desc' => 'Increase your play count to reach the thresholds needed for artist revenue.'],
                    ['title' => 'Discover Weekly Trigger', 'desc' => 'High play velocity tells Spotify your track is ready for a wider audience.'],
                    ['title' => 'Track Authority', 'desc' => 'Listeners are much more likely to play a song that already has thousands of streams.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Get your latest track the initial traction it needs to start trending globally.', 'icon' => 'fa-solid fa-bolt'],
                ['name' => 'Businesses', 'desc' => 'Establish credibility for your label\'s new releases with massive stream counts.', 'icon' => 'fa-solid fa-compact-disc'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach as a curator by showing high engagement on your tracks.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-play', 'features' => ['High Quality Plays', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Viral Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Impression Signal', 'Algorithm Velocity', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Editorial VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Global High Impact', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Viral Momentum', 'desc' => 'Give your tracks the initial push they need to trigger the Spotify recommendation engine.', 'icon' => 'fa-solid fa-rocket'],
                ['title' => 'Public Authority', 'desc' => 'A track with thousands of plays naturally attracts more organic engagement and trust.', 'icon' => 'fa-solid fa-check-to-slot'],
                ['title' => 'Algorithm Advantage', 'desc' => 'High play velocity tells the Spotify AI that your music is trending globally.', 'icon' => 'fa-solid fa-chart-area'],
                ['title' => 'Brand Growth', 'desc' => 'Consistent play counts build a professional image that attracts sponsorships.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Are these safe for royalties?', 'a' => 'We use high-quality, high-retention plays that are designed to be safe and natural.'],
                ['q' => 'Can I split plays?', 'a' => 'Yes! You can distribute plays across multiple tracks in a single order.'],
                ['q' => 'How soon will I see results?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need the public link to your Spotify track.']
            ]
        ];

        return $this->renderService($data);
    }

    public function spotifyListeners()
    {
        $data = [
            'name' => 'Buy Spotify Monthly Listeners',
            'keyword' => 'Buy Spotify Monthly Listeners Real',
            'title' => 'Buy Spotify Monthly Listeners | Boost Your Artist Reach',
            'description' => 'Increase your Spotify monthly listeners instantly. High quality, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-headphones',
            'color' => '#1DB954',
            'why_matter' => [
                'title' => 'Why Monthly Listeners Matter',
                'desc' => 'Monthly Listeners are the ultimate metric of an artist\'s current popularity on Spotify. It tells fans, curators, and labels how many people are consistently listening to your music. A high listener count is the key to proving you have a real, dedicated audience.',
                'points' => [
                    ['title' => 'Artist Credibility', 'desc' => 'The first metric labels and curators check when researching an artist.'],
                    ['title' => 'Ranking Advantage', 'desc' => 'Higher listener counts help you rank higher in Spotify\'s search results.'],
                    ['title' => 'Fanbase Growth', 'desc' => 'Show your reach and impact as a growing artist in the music scene.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Establish your artist brand by showing a massive, consistent audience.', 'icon' => 'fa-solid fa-microphone'],
                ['name' => 'Businesses', 'desc' => 'Build credibility for your label\'s roster with massive listener counts.', 'icon' => 'fa-solid fa-briefcase'],
                ['name' => 'Influencers', 'desc' => 'Maintain your status as a leader and build a truly multi-platform audience.', 'icon' => 'fa-solid fa-star']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Listeners', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Artist Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Listeners', 'Profile Authority', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Global VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Impact Reach', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Maximum Reach', 'desc' => 'Build a consistent audience that signals massive reach to labels and curators.', 'icon' => 'fa-solid fa-tower-broadcast'],
                ['title' => 'Artist Trust', 'desc' => 'A high listener count instantly signals authority and credibility to anyone who visits your profile.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Organic Growth', 'desc' => 'Listeners attract more organic fans by showing that your music is trending.', 'icon' => 'fa-solid fa-chart-line'],
                ['title' => 'Build Influence', 'desc' => 'Show potential partners and brand sponsors that you have a massive, engaged audience.', 'icon' => 'fa-solid fa-crown']
            ],
            'faqs' => [
                ['q' => 'Are these real listeners?', 'a' => 'We provide high-quality listeners from profiles that look active and natural to ensure safety.'],
                ['q' => 'How long do they last?', 'a' => 'Monthly listeners reflect your audience over a 28-day rolling period.'],
                ['q' => 'When will I see results?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe, organic-style delivery methods that fully comply with Spotify\'s standards.']
            ]
        ];

        return $this->renderService($data);
    }

    /* --- AUDIOMACK SEO PAGES --- */

    public function audiomackFollowers()
    {
        $data = [
            'name' => 'Buy Audiomack Followers',
            'keyword' => 'Buy Audiomack Artist Followers',
            'title' => 'Buy Audiomack Followers | Boost Your Artist Authority',
            'description' => 'Increase your Audiomack followers instantly. High quality members, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-users',
            'color' => '#FFA200',
            'why_matter' => [
                'title' => 'Why Audiomack Followers Matter',
                'desc' => 'On Audiomack, your follower count determines your ranking in the trending charts. High follower counts tell the Audiomack curators that you have a dedicated fan base, increasing your chances of being featured in "Trending" and "Top Artist" lists.',
                'points' => [
                    ['title' => 'Chart Visibility', 'desc' => 'New artists are much more likely to join a large, active community.'],
                    ['title' => 'Trending Authority', 'desc' => 'Larger artist profiles rank higher in Audiomack global search results.'],
                    ['title' => 'Artist Credibility', 'desc' => 'Build a massive base for your news, signals, or product updates.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Start your community journey by building a strong member base immediately.', 'icon' => 'fa-solid fa-user-group'],
                ['name' => 'Businesses', 'desc' => 'Establish a professional brand presence and build trust with your fans.', 'icon' => 'fa-solid fa-store'],
                ['name' => 'Influencers', 'desc' => 'Maintain your reach and authority by building a massive Audiomack following.', 'icon' => 'fa-solid fa-bullhorn']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-check', 'features' => ['High Quality Followers', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Active Premium', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Active Profiles', 'Algorithm Booster', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Trending VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Authority Reach', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Instant Authority', 'desc' => 'A large follower count instantly signals legitimacy and trust to anyone who finds your profile.', 'icon' => 'fa-solid fa-certificate'],
                ['title' => 'Audiomack SEO', 'desc' => 'Higher follower counts help your channel rank higher in Audiomack\'s global search engine.', 'icon' => 'fa-solid fa-magnifying-glass-chart'],
                ['title' => 'Rapid Traction', 'desc' => 'Building a solid foundation helps attract organic users by showing your community is popular.', 'icon' => 'fa-solid fa-rocket'],
                ['title' => 'Artist Status', 'desc' => 'Show potential partners and sponsors that you have a massive, established reach.', 'icon' => 'fa-solid fa-handshake-angle']
            ],
            'faqs' => [
                ['q' => 'Are these real followers?', 'a' => 'We provide high-quality followers from profiles that look active and natural to ensure safety.'],
                ['q' => 'Is it safe for my account?', 'a' => 'Absolutely. We use safe delivery methods that fully comply with Audiomack\'s standards.'],
                ['q' => 'When will I see results?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Do you need my login?', 'a' => 'Never. We only need the public link to your Audiomack artist profile.']
            ]
        ];

        return $this->renderService($data);
    }

    public function audiomackPlays()
    {
        $data = [
            'name' => 'Buy Audiomack Plays',
            'keyword' => 'Buy Audiomack Track Plays',
            'title' => 'Buy Audiomack Plays | Boost Your Track Credibility',
            'description' => 'Increase your Audiomack track plays instantly. High quality, fast delivery, and safe growth.',
            'icon' => 'fa-solid fa-play',
            'color' => '#FFA200',
            'why_matter' => [
                'title' => 'Why Audiomack Plays Matter',
                'desc' => 'On Audiomack, plays tell the story of your track\'s popularity. High play counts on every song signal to new listeners and curators that your music is being played and valued. It builds a "Trending Track" atmosphere that keeps members engaged and attracts new ones.',
                'points' => [
                    ['title' => 'Trending Signal', 'desc' => 'Show your listeners that your tracks are highly active and engaged.'],
                    ['title' => 'Track Authority', 'desc' => 'Individual updates look more authoritative and credible with high view counts.'],
                    ['title' => 'Viral Velocity', 'desc' => 'High plays encourage listeners to share your music with their own circles.']
                ]
            ],
            'audience' => [
                ['name' => 'Individuals', 'desc' => 'Ensure your updates and signals get the "Initial Traction" they deserve.', 'icon' => 'fa-solid fa-bolt'],
                ['name' => 'Businesses', 'desc' => 'Build trust in your product announcements and market updates.', 'icon' => 'fa-solid fa-chart-line'],
                ['name' => 'Influencers', 'desc' => 'Show brand partners that your audience is highly active and responsive.', 'icon' => 'fa-solid fa-user-check']
            ],
            'tiers' => [
                ['name' => 'High Quality', 'icon' => 'fa-solid fa-play', 'features' => ['High Quality Plays', 'Fast Delivery', 'No Password Required', '24/7 Support']],
                ['name' => 'Artist Booster', 'icon' => 'fa-solid fa-bolt', 'features' => ['Real Impression Signal', 'Algorithm Velocity', 'Guaranteed Delivery', 'Safe & Secure']],
                ['name' => 'Trending VIP', 'icon' => 'fa-solid fa-crown', 'features' => ['Max Authority Reach', 'Instant Start', 'Priority Delivery', 'Priority VIP Support']]
            ],
            'benefits' => [
                ['title' => 'Build Trust', 'desc' => 'High play counts on your tracks signal to new and existing fans that your content is highly valued.', 'icon' => 'fa-solid fa-shield-halved'],
                ['title' => 'Trending Image', 'desc' => 'Create a professional "Trending Track" image that attracts more organic listeners and engagement.', 'icon' => 'fa-solid fa-users-viewfinder'],
                ['title' => 'Instant Impact', 'desc' => 'See results in minutes and watch your tracks gain immediate social proof and authority.', 'icon' => 'fa-solid fa-bolt-lightning'],
                ['title' => 'Artist Growth', 'desc' => 'Consistent high plays build a professional image that attracts high-value sponsorships.', 'icon' => 'fa-solid fa-briefcase']
            ],
            'faqs' => [
                ['q' => 'Can I split plays?', 'a' => 'Yes! You can distribute plays across your most recent tracks or select specific ones.'],
                ['q' => 'Are these real plays?', 'a' => 'We provide high-quality engagement that looks natural and adheres to Audiomack\'s safety standards.'],
                ['q' => 'How soon will results show?', 'a' => 'Orders typically start processing within minutes of order confirmation.'],
                ['q' => 'Is it safe?', 'a' => 'Absolutely. We use safe, established delivery methods to ensure your account is never at risk.']
            ]
        ];

        return $this->renderService($data);
    }

    private function renderService($data)
    {
        $name = $data['name'];
        $title = $data['title'];
        $keyword = $data['keyword'];
        $description = $data['description'];
        $meta = ['icon' => $data['icon'], 'color' => $data['color']];
        $benefits = $data['benefits'];
        $faqs = $data['faqs'];
        $tiers = $data['tiers'];
        $audience = $data['audience'] ?? [];
        $whyMatter = $data['why_matter'] ?? null;

        // Extract Platform & Unit for dynamic UI
        $platform = 'Instagram';
        if (str_contains($name, 'TikTok')) $platform = 'TikTok';
        if (str_contains($name, 'YouTube')) $platform = 'YouTube';
        if (str_contains($name, 'X')) $platform = 'X';
        if (str_contains($name, 'Facebook')) $platform = 'Facebook';
        if (str_contains($name, 'Telegram')) $platform = 'Telegram';
        if (str_contains($name, 'Spotify')) $platform = 'Spotify';
        if (str_contains($name, 'Audiomack')) $platform = 'Audiomack';
        
        $platformSlug = strtolower($platform);
        $serviceUnit = str_replace('Buy ' . $platform . ' ', '', $name);
        
        return view('service-landing', compact(
            'name', 'title', 'keyword', 'description', 'meta', 'benefits', 'faqs', 'tiers', 'audience', 'whyMatter',
            'platform', 'platformSlug', 'serviceUnit'
        ));
    }
}
