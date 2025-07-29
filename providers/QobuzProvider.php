<?php

require_once 'MusicProvider.php';

class QobuzProvider extends MusicProvider {
    
    public function __construct() {
        // Qobuz is source-only, no search URL needed for destinations
        parent::__construct('qobuz', '');
    }
    
    public function parseUrl($url) {
        // Qobuz album URLs: https://play.qobuz.com/album/abc123
        if (preg_match('/play\.qobuz\.com\/album\/([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return ['type' => 'album', 'id' => $matches[1]];
        }
        
        // Qobuz track URLs: https://play.qobuz.com/track/123456
        if (preg_match('/play\.qobuz\.com\/track\/([0-9]+)/', $url, $matches)) {
            return ['type' => 'track', 'id' => $matches[1]];
        }
        
        return null;
    }
    
    public function getTrackInfo($data) {
        // For now, extract basic info from URL structure
        // In a full implementation, you'd call Qobuz API here
        
        if ($data['type'] === 'track') {
            // Return basic structure - could be enhanced with actual API calls
            return [
                'name' => 'Track from Qobuz',
                'artists' => [['name' => 'Unknown Artist']],
                'album_art' => null
            ];
        }
        
        if ($data['type'] === 'album') {
            return [
                'name' => 'Album from Qobuz', 
                'artists' => [['name' => 'Unknown Artist']],
                'album_art' => null
            ];
        }
        
        return null;
    }
    
    public function getSearchUrl($trackName, $artistName) {
        // Qobuz is source-only, so this shouldn't be called
        // But return a fallback just in case
        return 'https://play.qobuz.com/search?q=' . urlencode($trackName . ' ' . $artistName);
    }
}