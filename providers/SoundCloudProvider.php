<?php

require_once 'MusicProvider.php';

class SoundCloudProvider extends MusicProvider {
    
    public function __construct() {
        parent::__construct('soundcloud', 'https://soundcloud.com/search?q=');
    }
    
    public function parseUrl($url) {
        if (preg_match('/soundcloud\.com\/([^\/]+)\/([^\/\?]+)/', $url, $matches)) {
            return ['user' => $matches[1], 'track' => $matches[2]];
        }
        return null;
    }
    
    public function getTrackInfo($data) {
        // Placeholder - SoundCloud API integration needed
        $user = $data['user'];
        $track = $data['track'];
        
        return [
            'name' => ucwords(str_replace('-', ' ', $track)),
            'artists' => [['name' => ucwords(str_replace('-', ' ', $user))]]
        ];
    }
    
    public function getSearchUrl($trackName, $artistName) {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
}