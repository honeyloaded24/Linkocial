<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use App\Models\Service;
use App\Models\Blog;
use Illuminate\Support\Facades\Route;

class SitemapController extends Controller
{
    /**
     * Generate a dynamic XML sitemap.
     */
    public function index()
    {
        $xmlDeclaration = '<?xml version="1.0" encoding="UTF-8"?>';
        $urls = [];

        // 1. Static & Main Landing Pages
        $urls[] = [
            'loc' => route('landing'),
            'priority' => '1.0',
            'changefreq' => 'daily'
        ];

        // 2. Platform Specific Pages
        $platforms = ['instagram', 'tiktok', 'youtube', 'facebook', 'x', 'telegram', 'spotify', 'audiomack'];
        foreach ($platforms as $platform) {
            $urls[] = [
                'loc' => route('platform', ['platform' => $platform]),
                'priority' => '0.9',
                'changefreq' => 'weekly'
            ];
        }

        // 3. Specialized SEO Pages
        $seoRoutes = [
            'services.ig-followers', 'services.ig-comments', 'services.ig-likes',
            'services.tk-followers', 'services.tk-likes', 'services.tk-views',
            'services.yt-subscribers', 'services.yt-views', 'services.yt-watchtime',
            'services.x-followers', 'services.x-likes', 'services.x-reposts',
            'services.fb-page-likes', 'services.fb-post-likes', 'services.fb-followers',
            'services.tg-members', 'services.tg-views',
            'services.sp-followers', 'services.sp-plays', 'services.sp-listeners',
            'services.am-followers', 'services.am-plays'
        ];

        foreach ($seoRoutes as $routeName) {
            if (Route::has($routeName)) {
                $urls[] = [
                    'loc' => route($routeName),
                    'priority' => '0.8',
                    'changefreq' => 'weekly'
                ];
            }
        }

        // 4. Info Pages
        $infoPages = ['info.about', 'info.faq', 'info.contact', 'info.blog'];
        foreach ($infoPages as $info) {
            if (Route::has($info)) {
                $urls[] = [
                    'loc' => route($info),
                    'priority' => '0.5',
                    'changefreq' => 'monthly'
                ];
            }
        }

        // 5. Dynamic Blog Posts
        $posts = Blog::where('status', 'published')->latest()->get();
        foreach ($posts as $post) {
            $urls[] = [
                'loc' => route('info.blog.show', ['slug' => $post->slug]),
                'lastmod' => $post->updated_at->toAtomString(),
                'priority' => '0.7',
                'changefreq' => 'weekly'
            ];
        }

        return response()->view('seo.sitemap', compact('urls', 'xmlDeclaration'))->header('Content-Type', 'text/xml');
    }
}
