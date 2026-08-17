<?php

namespace App\Http\Controllers;

use App\Models\Frontend;
use App\Models\Page;
use App\Models\Store;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function index(): Response
    {
        $baseUrl = config('app.url', 'https://www.liztodelivery.com');

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<?xml-stylesheet type="text/xsl" href="' . $baseUrl . '/sitemap.xsl"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"' . "\n";
        $xml .= '        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1"' . "\n";
        $xml .= '        xmlns:xhtml="http://www.w3.org/1999/xhtml">' . "\n";

        // Static pages
        $staticPages = [
            '/'              => ['changefreq' => 'daily',   'priority' => '1.0'],
            '/delivery'      => ['changefreq' => 'daily',   'priority' => '0.9'],
            '/taxi'          => ['changefreq' => 'weekly',  'priority' => '0.8'],
            '/contact'       => ['changefreq' => 'monthly', 'priority' => '0.7'],
            '/blog'          => ['changefreq' => 'daily',   'priority' => '0.8'],
        ];

        foreach ($staticPages as $path => $config) {
            $xml .= $this->urlEntry($baseUrl . $path, $config['changefreq'], $config['priority']);
        }

        // Stores
        $stores = Store::where('status', 'active')
            ->select('id', 'slug', 'name', 'updated_at')
            ->get();

        foreach ($stores as $store) {
            $slug = $store->slug ?? $store->id;
            $xml .= $this->urlEntry(
                $baseUrl . '/delivery/tienda/' . $slug,
                'weekly',
                '0.8',
                $store->updated_at
            );
        }

        // Blog posts
        $blogs = Frontend::where('data_keys', 'blog.element')
            ->where('status', 1)
            ->select('id', 'slug', 'updated_at')
            ->latest('id')
            ->get();

        foreach ($blogs as $blog) {
            if (!$blog->slug) continue;
            $xml .= $this->urlEntry(
                $baseUrl . '/blog/' . $blog->slug,
                'weekly',
                '0.7',
                $blog->updated_at
            );
        }

        $xml .= '</urlset>';

        return response($xml, 200)
            ->header('Content-Type', 'application/xml; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=3600');
    }

    private function urlEntry(string $loc, string $changefreq = 'monthly', string $priority = '0.5', $lastmod = null): string
    {
        $lastmodTag = '';
        if ($lastmod) {
            $lastmodTag = "\n            <lastmod>" . $lastmod->format('Y-m-d\TH:i:sP') . "</lastmod>";
        }

        return <<<XML
        <url>
            <loc>{$loc}</loc>{$lastmodTag}
            <changefreq>{$changefreq}</changefreq>
            <priority>{$priority}</priority>
        </url>

XML;
    }
}
