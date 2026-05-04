<?php

namespace Database\Seeders;

use App\Models\Blog;
use Illuminate\Database\Seeder;

class BlogSeeder extends Seeder
{
    public function run(): void
    {
        $posts = [
            [
                'title' => 'How to Trigger the TikTok FYP in 2024',
                'category' => 'TikTok Strategy',
                'excerpt' => 'Discover the specific metrics TikTok uses to promote videos and how engagement velocity plays a key role in going viral.',
                'content' => "Going viral on TikTok isn't just about luck—it's about understanding the 'Velocity Threshold'. 

The FYP (For You Page) algorithm prioritizes completion rate and engagement-to-view ratios. If your video gets high engagement in the first 60 minutes, TikTok triggers a 'Push' phase.

## 1. The Power of Completion Rate
Your video must be watched until the end. This is the #1 signal to TikTok that your content is valuable.

## 2. Engagement Velocity
The faster you get likes and comments, the faster you move through the algorithm tiers. This is why many top creators use promotion services to spark that initial fire.

## 3. SEO on TikTok
Yes, keywords matter! Include high-intent keywords in your captions and on-screen text to help TikTok categorize your content.",
                'status' => 'published',
            ],
            [
                'title' => 'The Power of Instagram Social Proof',
                'category' => 'Instagram Growth',
                'excerpt' => 'Why your follower count is the most important trust metric for your brand\'s growth and conversion rate.',
                'content' => "In the digital age, your Instagram profile is your business card. 

When a new user lands on your page, they make a decision in less than 3 seconds: 'Is this account worth following?' This decision is almost entirely driven by social proof.

## 1. The Numbers Game
A profile with 10,000 followers instantly commands more authority than one with 100. It signals to the world that you are an established leader in your niche.

## 2. Trust and Conversion
If you are running an e-commerce brand or offering services, social proof directly impacts your sales. Users are 80% more likely to buy from a brand that has a significant social presence.

## 3. Triggering Organic Reach
Instagram's algorithm is risk-averse. It prefers to show content from accounts that already have high engagement. By building your baseline followers and likes, you make it safer for the algorithm to promote you.",
                'status' => 'published',
            ],
            [
                'title' => 'Spotify Editorial Playlist Secrets',
                'category' => 'Music Industry',
                'excerpt' => 'How independent artists can use listener velocity to get noticed by Spotify\'s major editorial teams.',
                'content' => "Getting on a Spotify Editorial playlist like 'Today's Top Hits' is the dream for every artist. But how do you get noticed by the curators?

The answer is data-driven authority. Curators look at 'Listen-Through Rates' and 'Save Velocity'.

## 1. Listener Retention
Spotify tracks how many people listen to your track without skipping. High retention signals a hit.

## 2. Playlist Saves
When a user adds your song to their personal library, it's a massive green flag for the algorithm.

## 3. Triggering the Discover Weekly Engine
By driving consistent, high-quality plays to your new release, you can force Spotify's 'Discover Weekly' algorithm to start recommending you to similar listeners. This organic ripple effect starts with a solid foundation of initial plays.",
                'status' => 'published',
            ],
        ];

        foreach ($posts as $post) {
            Blog::create($post);
        }
    }
}
