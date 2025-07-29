<?php

require_once 'SpotifyProvider.php';
require_once 'YouTubeProvider.php';
require_once 'AppleMusicProvider.php';
require_once 'DeezerProvider.php';
require_once 'TidalProvider.php';
require_once 'SoundCloudProvider.php';

class ProviderManager {
    private $providers = [];
    private $searchUrls;
    
    public function __construct() {
        // Define search URLs without instantiating providers
        $this->searchUrls = [
            'spotify' => 'https://open.spotify.com/search/',
            'youtube' => 'https://music.youtube.com/search?q=',
            'apple' => 'https://music.apple.com/us/search?term=',
            'deezer' => 'https://www.deezer.com/search/',
            'tidal' => 'https://tidal.com/browse/search?q=',
            'soundcloud' => 'https://soundcloud.com/search?q='
        ];
    }
    
    private function getProvider($name) {
        if (!isset($this->providers[$name])) {
            switch ($name) {
                case 'spotify':
                    $this->providers[$name] = new SpotifyProvider();
                    break;
                case 'youtube':
                    $this->providers[$name] = new YouTubeProvider();
                    break;
                case 'apple':
                    $this->providers[$name] = new AppleMusicProvider();
                    break;
                case 'deezer':
                    $this->providers[$name] = new DeezerProvider();
                    break;
                case 'tidal':
                    $this->providers[$name] = new TidalProvider();
                    break;
                case 'soundcloud':
                    $this->providers[$name] = new SoundCloudProvider();
                    break;
                default:
                    return null;
            }
        }
        return $this->providers[$name];
    }
    
    public function parseUrl($url) {
        // Try each provider type
        $providerNames = ['spotify', 'youtube', 'apple', 'deezer', 'tidal', 'soundcloud'];
        foreach ($providerNames as $name) {
            $provider = $this->getProvider($name);
            if ($provider) {
                $result = $provider->parseUrl($url);
                if ($result !== null) {
                    return ['platform' => $name, 'data' => $result];
                }
            }
        }
        return null;
    }
    
    public function getTrackInfo($platform, $data) {
        $provider = $this->getProvider($platform);
        if ($provider) {
            return $provider->getTrackInfo($data);
        }
        return null;
    }
    
    public function getSearchUrls() {
        return $this->searchUrls;
    }
    
    public function getMainProviders() {
        // Only show main providers on home screen
        $mainProviders = ['spotify', 'youtube', 'apple'];
        $result = [];
        foreach ($mainProviders as $provider) {
            if (isset($this->searchUrls[$provider])) {
                $result[$provider] = $this->searchUrls[$provider];
            }
        }
        return $result;
    }
    
    public function getSearchUrl($platform, $trackName, $artistName) {
        $provider = $this->getProvider($platform);
        if ($provider) {
            return $provider->getSearchUrl($trackName, $artistName);
        }
        return null;
    }
}