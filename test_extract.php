<?php
require_once 'ShortLinkCache.php';

$cache = new ShortLinkCache();

// Test the extractTrackId function via reflection
$reflection = new ReflectionClass($cache);
$method = $reflection->getMethod('extractTrackId');
$method->setAccessible(true);

$testUrls = [
    'open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh',
    'music.youtube.com/watch?v=dQw4w9WgXcQ',
    'music.apple.com/us/album/album/123456?i=789012'
];

foreach ($testUrls as $url) {
    $trackId = $method->invoke($cache, $url);
    echo "URL: $url\nTrack ID: " . ($trackId ?: 'null') . "\n\n";
}

// Test the full short code generation
foreach ($testUrls as $url) {
    $shortCode = $cache->generatePrefixedShortCode($url);
    echo "URL: $url\nShort Code: $shortCode\n\n";
}
?>