<?php

require_once 'MusicProvider.php';

class YouTubeProvider extends MusicProvider {
    
    public function __construct() {
        parent::__construct('youtube', 'https://music.youtube.com/search?q=');
    }
    
    public function parseUrl($url) {
        if (preg_match('/music\.youtube\.com\/watch\?.*v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return ['id' => $matches[1]];
        }
        return null;
    }
    
    public function getTrackInfo($data) {
        $videoId = $data['id'];
        
        // Try to get video info from YouTube's oEmbed API
        $oembedUrl = "https://www.youtube.com/oembed?url=https://www.youtube.com/watch?v=" . $videoId . "&format=json";
        $response = @file_get_contents($oembedUrl);
        
        if ($response) {
            $data = json_decode($response, true);
            if ($data && isset($data['title']) && isset($data['author_name'])) {
                $title = $data['title'];
                $author = $data['author_name'];
                
                // Try to split artist and song from title first
                if (preg_match('/^(.+?)\s*[-–—]\s*(.+)$/', $title, $parts)) {
                    return [
                        'name' => trim($parts[2]),
                        'artists' => [['name' => trim($parts[1])]]
                    ];
                } else {
                    // Use author_name as artist if no delimiter found in title
                    // Clean up "- Topic" suffix from YouTube auto-generated channels
                    $cleanAuthor = preg_replace('/\s*-\s*Topic\s*$/i', '', $author);
                    return [
                        'name' => $title,
                        'artists' => [['name' => $cleanAuthor]]
                    ];
                }
            }
        }
        
        return null;
    }
    
    public function getSearchUrl($trackName, $artistName) {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
}