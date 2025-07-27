<?php

require_once 'SpotifyProvider.php';
require_once 'YouTubeProvider.php';
require_once 'AppleMusicProvider.php';
require_once 'DeezerProvider.php';
require_once 'TidalProvider.php';
require_once 'SoundCloudProvider.php';

class ProviderManager {
    private $providers;
    private $searchUrls;
    
    public function __construct() {
        $this->providers = [
            'spotify' => new SpotifyProvider(),
            'youtube' => new YouTubeProvider(),
            'apple' => new AppleMusicProvider(),
            'deezer' => new DeezerProvider(),
            'tidal' => new TidalProvider(),
            'soundcloud' => new SoundCloudProvider()
        ];
        
        $this->searchUrls = [];
        foreach ($this->providers as $name => $provider) {
            $this->searchUrls[$name] = $provider->getBaseUrl();
        }
    }
    
    public function parseUrl($url) {
        foreach ($this->providers as $name => $provider) {
            $result = $provider->parseUrl($url);
            if ($result !== null) {
                return ['platform' => $name, 'data' => $result];
            }
        }
        return null;
    }
    
    public function getTrackInfo($platform, $data) {
        if (isset($this->providers[$platform])) {
            return $this->providers[$platform]->getTrackInfo($data);
        }
        return null;
    }
    
    public function getSearchUrls() {
        return $this->searchUrls;
    }
    
    public function getSearchUrl($platform, $trackName, $artistName) {
        if (isset($this->providers[$platform])) {
            return $this->providers[$platform]->getSearchUrl($trackName, $artistName);
        }
        return null;
    }
}