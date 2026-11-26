<?php
namespace App\Http\Controllers;

use App\Support\SiteSitemap;

class SitemapController extends Controller
{
    public function index(SiteSitemap $sitemap)
    {
        abort_unless($sitemap->enabled(), 404);
        return $this->xml('sitemapindex', 'sitemap', $sitemap->indexEntries());
    }

    public function section(SiteSitemap $sitemap, string $section, string $page = '1')
    {
        abort_unless($sitemap->enabled() && in_array($section, $sitemap->sections(), true), 404);
        $offset = ((int) $page - 1) * $sitemap->limit();
        $entries = [];
        foreach ($sitemap->entries($section) as $entry) {
            if ($offset-- > 0) continue;
            $entries[] = $entry;
            if (count($entries) >= $sitemap->limit()) break;
        }
        abort_if($entries === [], 404);
        return $this->xml('urlset', 'url', $entries);
    }

    private function xml(string $root, string $element, iterable $entries)
    {
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->startDocument('1.0', 'UTF-8');
        $xml->startElement($root);
        $xml->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        foreach ($entries as $entry) {
            $xml->startElement($element);
            $xml->writeElement('loc', $entry['loc']);
            if (!empty($entry['lastmod'])) $xml->writeElement('lastmod', $entry['lastmod']);
            $xml->endElement();
        }
        $xml->endElement();
        $xml->endDocument();
        return response($xml->outputMemory(), 200, ['Content-Type' => 'application/xml; charset=UTF-8', 'Cache-Control' => 'no-store', 'X-Robots-Tag' => 'noindex, follow']);
    }

    public function robots(SiteSitemap $sitemap)
    {
        return response($sitemap->robots(), 200, ['Content-Type' => 'text/plain; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }
}
