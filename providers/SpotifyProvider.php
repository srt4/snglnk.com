<?php

require_once 'MusicProvider.php';

class SpotifyProvider extends MusicProvider {
    private $clientId;
    private $clientSecret;
    private $accessToken;
    private $tokenExpiry;
    
    public function __construct() {
        parent::__construct('spotify', 'https://open.spotify.com/search/');
        
        // Load configuration
        $config = require dirname(__DIR__) . '/config.php';
        $this->clientId = $config['spotify']['client_id'];
        $this->clientSecret = $config['spotify']['client_secret'];
    }
    
    public function parseUrl($url) {
        if (preg_match('/(?:open\.spotify\.com\/track\/|spotify:track:)([a-zA-Z0-9]+)/', $url, $matches)) {
            return ['id' => $matches[1]];
        }
        return null;
    }
    
    public function getTrackInfo($data) {
        $accessToken = $this->getAccessToken();
        if (!$accessToken) {
            return null;
        }
        
        $trackId = $data['id'];
        $url = "https://api.spotify.com/v1/tracks/{$trackId}";
        $headers = [
            'Authorization: Bearer ' . $accessToken
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        $response = curl_exec($ch);
        curl_close($ch);

        $trackInfo = json_decode($response, true);
        
        if (!empty($trackInfo['name']) && !empty($trackInfo['artists'][0]['name'])) {
            $result = [
                'name' => $trackInfo['name'],
                'artists' => [['name' => $trackInfo['artists'][0]['name']]]
            ];
            
            // Add album artwork if available
            if (!empty($trackInfo['album']['images'][0]['url'])) {
                $result['album_art'] = $trackInfo['album']['images'][0]['url'];
            }
            
            return $result;
        }
        
        return null;
    }
    
    public function getSearchUrl($trackName, $artistName) {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
    
    private function getAccessToken() {
        // Check if we have a valid cached token
        if ($this->accessToken && $this->tokenExpiry && time() < $this->tokenExpiry) {
            return $this->accessToken;
        }
        
        $url = "https://accounts.spotify.com/api/token";
        $headers = [
            'Authorization: Basic ' . base64_encode($this->clientId . ':' . $this->clientSecret)
        ];
        $data = http_build_query([
            'grant_type' => 'client_credentials'
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $result = json_decode($response, true);
        if (isset($result['access_token'])) {
            $this->accessToken = $result['access_token'];
            // Cache for 55 minutes (tokens expire in 60 minutes)
            $this->tokenExpiry = time() + 3300;
            return $this->accessToken;
        }
        
        return null;
    }
}