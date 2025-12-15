<?php

require_once dirname(__DIR__) . '/TrackCache.php';
require_once 'SpotifyProvider.php';
require_once 'YouTubeProvider.php';
require_once 'AppleMusicProvider.php';
require_once 'DeezerProvider.php';
require_once 'TidalProvider.php';
require_once 'SoundCloudProvider.php';
require_once 'QobuzProvider.php';

class ProviderManager {
    private $providers = [];
    private $searchUrls;
    private $cache;
    
    public function __construct() {
        $this->cache = new TrackCache();
        
        // Occasionally clean up old cache entries (1 in 100 requests)
        if (rand(1, 100) === 1) {
            $this->cache->cleanup();
        }
        
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
                case 'qobuz':
                    $this->providers[$name] = new QobuzProvider();
                    break;
                default:
                    return null;
            }
        }
        return $this->providers[$name];
    }
    
    public function parseUrl($url) {
        // Try each provider type (including Qobuz as source)
        $providerNames = ['spotify', 'youtube', 'apple', 'deezer', 'tidal', 'soundcloud', 'qobuz'];
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
        // Generate cache key
        $trackKey = $this->cache->getTrackKey($platform, $data);
        
        // Try to get from cache first
        $cachedInfo = $this->cache->get($platform, $trackKey);
        if ($cachedInfo) {
            return $cachedInfo;
        }
        
        // Not in cache, fetch from provider
        $provider = $this->getProvider($platform);
        if ($provider) {
            $trackInfo = $provider->getTrackInfo($data);
            
            // Cache the result if successful
            if ($trackInfo && isset($trackInfo['name']) && isset($trackInfo['artists'][0]['name'])) {
                $this->cache->set($platform, $trackKey, $trackInfo);
            }
            
            return $trackInfo;
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
    
    public function getCacheStats() {
        return $this->cache->getStats();
    }

    public static function fetchUrl($url) {
        $options = [
            'http' => [
                'method' => 'GET',
                'header' => 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36 snglnk/1.0\r\n' .
                           'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8\r\n' .
                           'Accept-Language: en-US,en;q=0.9\r\n'
            ]
        ];
        $context = stream_context_create($options);
        return @file_get_contents($url, false, $context);
    }
}