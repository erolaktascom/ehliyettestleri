<?php

namespace App\Support;

use DOMDocument;
use DOMXPath;

class LectureReadingContent
{
    /** Add in-page anchors at render time; never rewrite the stored article. */
    public static function prepare(?string $content): array
    {
        $content ??= '';
        $original = ['html' => $content, 'headings' => []];
        if ($content === '') return $original;
        $document = new DOMDocument('1.0', 'UTF-8');
        $previousErrors = libxml_use_internal_errors(true);
        try {
            $loaded = $document->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body>'.$content.'</body></html>', LIBXML_NONET);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousErrors);
        }
        if (!$loaded) return $original;
        $xpath = new DOMXPath($document);
        $nodes = [];
        foreach ($xpath->query('//body//h2 | //body//h3') as $node) {
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if ($text !== '') $nodes[] = [$node, $text];
        }
        if (count($nodes) < 3) return $original;
        $ids = [];
        foreach ($xpath->query('//*[@id]') as $node) {
            $id = $node->getAttribute('id');
            $ids[$id] = ($ids[$id] ?? 0) + 1;
        }
        $headings = [];
        foreach ($nodes as $index => [$node, $text]) {
            $id = $node->getAttribute('id');
            if ($id === '' || ($ids[$id] ?? 0) > 1) {
                $id = 'lecture-section-'.($index + 1);
                while (isset($ids[$id])) $id .= '-section';
                $node->setAttribute('id', $id);
                $ids[$id] = 1;
            }
            $headings[] = ['anchor' => rawurlencode($id), 'title' => $text, 'level' => $node->tagName];
        }
        $html = '';
        foreach ($document->getElementsByTagName('body')->item(0)->childNodes as $node) $html .= $document->saveHTML($node);
        return ['html' => $html, 'headings' => $headings];
    }
}
