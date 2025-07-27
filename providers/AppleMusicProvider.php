<?php

require_once 'MusicProvider.php';

class AppleMusicProvider extends MusicProvider {
    
    public function __construct() {
        parent::__construct('apple', 'https://music.apple.com/search?term=');
    }
    
    public function parseUrl($url) {
        // Apple Music URLs - album format
        if (preg_match('/music\.apple\.com\/[^\/]+\/album\/([^\/]+)\/(\d+)\?i=(\d+)/', $url, $matches)) {
            return ['album_name' => $matches[1], 'album_id' => $matches[2], 'track_id' => $matches[3]];
        }
        
        // Apple Music URLs - song format
        if (preg_match('/music\.apple\.com\/[^\/]+\/song\/([^\/\?]+)\/(\d+)/', $url, $matches)) {
            return ['song_name' => $matches[1], 'track_id' => $matches[2]];
        }
        
        return null;
    }
    
    public function getTrackInfo($data) {
        $trackId = $data['track_id'];
        
        // Try iTunes Search API first
        $itunesUrl = "https://itunes.apple.com/lookup?id={$trackId}&entity=song";
        $response = @file_get_contents($itunesUrl);
        
        if ($response) {
            $apiData = json_decode($response, true);
            if ($apiData && isset($apiData['results'][0])) {
                $track = $apiData['results'][0];
                if (isset($track['trackName']) && isset($track['artistName'])) {
                    $result = [
                        'name' => $track['trackName'],
                        'artists' => [['name' => $track['artistName']]]
                    ];
                    
                    // Add album artwork if available (use 100x100 size)
                    if (isset($track['artworkUrl100'])) {
                        $result['album_art'] = $track['artworkUrl100'];
                    }
                    
                    return $result;
                }
            }
        }
        
        // Fallback: clean up album/song name from URL
        $name = isset($data['album_name']) ? $data['album_name'] : $data['song_name'];
        $cleanName = ucwords(str_replace('-', ' ', $name));
        return [
            'name' => $cleanName,
            'artists' => [['name' => 'Unknown Artist']]
        ];
    }
    
    public function getSearchUrl($trackName, $artistName) {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
}