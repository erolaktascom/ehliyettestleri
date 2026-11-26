<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use InvalidArgumentException;

class QuestionMedia
{
    public static function embed(string $input): string
    {
        $input = trim($input);
        $native = false;
        if (str_starts_with($input, '<')) {
            $doc = self::document($input);
            $element = $doc->getElementsByTagName('iframe')->item(0) ?? $doc->getElementsByTagName('video')->item(0);
            if (!$element) throw new InvalidArgumentException('Video bağlantısı veya iframe/video kodu girin.');
            $native = $element->tagName === 'video';
            $input = $element->getAttribute('src') ?: ($element->getElementsByTagName('source')->item(0)?->getAttribute('src') ?? '');
        }
        $parts = parse_url($input);
        $native = $native || preg_match('/\.(mp4|webm|ogg|ogv)$/i', $parts['path'] ?? '');
        if (!self::safeUrl($input, (bool) $native)) throw new InvalidArgumentException('Geçerli bir http/https bağlantısı girin. Yerel dosyalarda / ile başlayan video yolu kullanın.');
        if (!$native) {
            $host = strtolower($parts['host'] ?? '');
            if (in_array($host, array_filter([strtolower(parse_url(config('app.url'), PHP_URL_HOST) ?? ''), strtolower(request()->getHost()), 'localhost', '127.0.0.1', '[::1]']))) {
                throw new InvalidArgumentException('Site içindeki videoları iframe yerine doğrudan video dosyası bağlantısıyla ekleyin.');
            }
            parse_str($parts['query'] ?? '', $query);
            $path = $parts['path'] ?? '';
            $id = null;
            if (in_array($host, ['youtube.com', 'www.youtube.com', 'm.youtube.com'])) {
                $id = $query['v'] ?? (preg_match('~^/(?:shorts|embed)/([\w-]+)~', $path, $m) ? $m[1] : null);
            } elseif ($host === 'youtu.be') {
                $id = trim($path, '/');
            }
            if (is_string($id) && preg_match('/^[\w-]+$/', $id)) $input = 'https://www.youtube.com/embed/'.$id;
            if (in_array($host, ['vimeo.com', 'www.vimeo.com']) && preg_match('~^/(\d+)$~', $path, $m)) $input = 'https://player.vimeo.com/video/'.$m[1];
        }
        $src = htmlspecialchars($input, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return $native
            ? '<video class="ql-native-video" src="'.$src.'" controls preload="metadata" style="width:100%;max-height:480px"></video>'
            : '<iframe class="ql-video" src="'.$src.'" title="Soru videosu" sandbox="allow-scripts allow-same-origin allow-presentation" allow="fullscreen; picture-in-picture" allowfullscreen loading="lazy" referrerpolicy="strict-origin-when-cross-origin" style="width:100%;aspect-ratio:16/9;border:0"></iframe>';
    }

    private static function safeUrl(string $url, bool $local = false): bool
    {
        if (preg_match('/[\x00-\x20\\\\]/', $url)) return false;
        if ($local && str_starts_with($url, '/') && !str_starts_with($url, '//')) return true;
        $parts = parse_url($url);
        return $parts && in_array(strtolower($parts['scheme'] ?? ''), ['http', 'https']) && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass']);
    }

    private static function document(string $html): DOMDocument
    {
        $doc = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        return $doc;
    }

    public static function clean(string $html): string
    {
        if ($html === '') return '';
        $doc = self::document($html);
        $walk = function ($parent) use (&$walk, $doc) {
            foreach (iterator_to_array($parent->childNodes) as $node) {
                if (!$node instanceof DOMElement) continue;
                $tag = strtolower($node->tagName);
                if (in_array($tag, ['iframe', 'video'])) {
                    try {
                        $media = self::document(self::embed($doc->saveHTML($node)));
                        $parent->replaceChild($doc->importNode($media->getElementsByTagName('body')->item(0)->firstChild, true), $node);
                    } catch (InvalidArgumentException) { $parent->removeChild($node); }
                    continue;
                }
                if (in_array($tag, ['script', 'style', 'object', 'embed', 'svg', 'math', 'form', 'input', 'button', 'textarea'])) {
                    $parent->removeChild($node);
                    continue;
                }
                $walk($node);
                if (!in_array($tag, ['p', 'br', 'strong', 'b', 'em', 'i', 'u', 's', 'h2', 'h3', 'ol', 'ul', 'li', 'blockquote', 'pre', 'code', 'span', 'a', 'img', 'div'])) {
                    while ($node->firstChild) $parent->insertBefore($node->firstChild, $node);
                    $parent->removeChild($node);
                    continue;
                }
                foreach (iterator_to_array($node->attributes) as $attr) {
                    $keep = ($tag === 'a' && $attr->name === 'href' && self::safeUrl($attr->value, true))
                        || ($tag === 'img' && $attr->name === 'src' && self::safeUrl($attr->value, true))
                        || ($attr->name === 'class' && preg_match('/^(?:ql-[a-z0-9-]+\s*)+$/', $attr->value))
                        || ($tag === 'li' && $attr->name === 'data-list' && in_array($attr->value, ['ordered', 'bullet']))
                        || ($attr->name === 'style' && preg_match('/^(?:(?:color|background-color|text-align):\s*(?:#[0-9a-fA-F]{3,8}|[a-z]+|rgb\([\d,\s]+\))\s*;?\s*)+$/', $attr->value));
                    if (!$keep) $node->removeAttribute($attr->name);
                }
            }
        };
        $body = $doc->getElementsByTagName('body')->item(0);
        $walk($body);
        return implode('', array_map(fn ($node) => $doc->saveHTML($node), iterator_to_array($body->childNodes)));
    }
}
