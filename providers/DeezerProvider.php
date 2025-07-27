<?php

require_once 'MusicProvider.php';

class DeezerProvider extends MusicProvider {
    
    public function __construct() {
        parent::__construct('deezer', 'https://www.deezer.com/search/');
    }
    
    public function parseUrl($url) {
        if (preg_match('/deezer\.com\/[^\/]+\/track\/(\d+)/', $url, $matches)) {
            return ['id' => $matches[1]];
        }
        return null;
    }
    
    public function getTrackInfo($data) {
        $trackId = $data['id'];
        $url = "https://api.deezer.com/track/" . $trackId;
        $response = @file_get_contents($url);
        
        if ($response) {
            $apiData = json_decode($response, true);
            if ($apiData && !isset($apiData['error'])) {
                $result = [
                    'name' => $apiData['title'],
                    'artists' => [['name' => $apiData['artist']['name']]]
                ];
                
                // Add album artwork if available (use medium size)
                if (isset($apiData['album']['cover_medium'])) {
                    $result['album_art'] = $apiData['album']['cover_medium'];
                }
                
                return $result;
            }
        }
        
        return null;
    }
    
    public function getSearchUrl($trackName, $artistName) {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
}