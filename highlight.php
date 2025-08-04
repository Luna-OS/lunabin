<?php
require_once __DIR__ . '/vendor/autoload.php'; // Autoloader für Composer-Packages

use Highlight\Highlighter;

function highlightCode(string $code, string $language = 'plaintext'): string {
    $hl = new Highlighter();
    try {
        $highlighted = $hl->highlight($language, $code);
        return '<pre><code class="hljs ' . htmlspecialchars($highlighted->language) . '">'
            . $highlighted->value
            . '</code></pre>';
    } catch (Exception $e) {
        
        return '<pre><code>' . htmlspecialchars($code) . '</code></pre>';
    }
}


