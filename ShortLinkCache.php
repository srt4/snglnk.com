<?php
class ShortLinkCache {
    public function __construct() {
        // No database needed - we don't track short links internally
    }
    
    public function createShortLink($originalUrl, $trackName = null, $artistName = null, $albumArt = null) {
        // Simply generate the short code based on provider track ID
        return $this->generateShortCode($originalUrl);
    }
    
    public function generateShortCode($originalUrl) {
        // Extract track ID from the URL
        $trackId = $this->extractTrackId($originalUrl);
        if ($trackId) {
            $provider = $this->getProviderPrefix($originalUrl);
            return $provider . '/' . $trackId;
        }
        
        return null; // Can't create short link without provider track ID
    }
    
    private function getProviderPrefix($url) {
        if (strpos($url, 'spotify.com') !== false) return 's';
        if (strpos($url, 'youtube.com') !== false) return 'y';  
        if (strpos($url, 'apple.com') !== false) return 'a';
        
        return null; // Only support main providers
    }
    
    private function extractTrackId($url) {
        // Remove protocol if present
        $url = preg_replace('/^https?:\/\//', '', $url);
        
        // Spotify: open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh
        if (preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // YouTube Music: music.youtube.com/watch?v=dQw4w9WgXcQ
        if (preg_match('/music\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }
        
        // Apple Music: music.apple.com/us/album/song-name/1234567890?i=0987654321
        if (preg_match('/music\.apple\.com\/.*\/([0-9]+)\?i=([0-9]+)/', $url, $matches)) {
            return $matches[2]; // Use the track ID part
        }
        
        // Apple Music album format: music.apple.com/us/album/album-name/1234567890
        if (preg_match('/music\.apple\.com\/.*\/([0-9]+)$/', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    public function getByCode($shortCode) {
        // Reconstruct original URL from provider short code
        return $this->reconstructOriginalUrl($shortCode);
    }
    
    private function reconstructOriginalUrl($shortCode) {
        if (!preg_match('/^([say])\/(.+)$/', $shortCode, $matches)) {
            return null;
        }
        
        $provider = $matches[1];
        $trackId = $matches[2];
        
        switch ($provider) {
            case 's': // Spotify
                return ['original_url' => "open.spotify.com/track/{$trackId}"];
            case 'y': // YouTube
                return ['original_url' => "music.youtube.com/watch?v={$trackId}"];
            case 'a': // Apple Music
                return ['original_url' => "music.apple.com/us/album/1/{$trackId}?i={$trackId}"];
            default:
                return null;
        }
    }
    
    public function incrementClicks($shortCode) {
        // No tracking - clicks are not stored
        return true;
    }
    
    public function getStats() {
        return [
            'shortlinks_enabled' => true,
            'tracking_enabled' => false,
            'format' => '{provider_char}/{provider_id}'
        ];
    }
}