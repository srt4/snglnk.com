<?php

abstract class MusicProvider {
    protected $name;
    protected $baseUrl;
    
    public function __construct($name, $baseUrl) {
        $this->name = $name;
        $this->baseUrl = $baseUrl;
    }
    
    public function getName() {
        return $this->name;
    }
    
    public function getBaseUrl() {
        return $this->baseUrl;
    }
    
    // Abstract methods that each provider must implement
    abstract public function parseUrl($url);
    abstract public function getTrackInfo($data);
    abstract public function getSearchUrl($trackName, $artistName);
    
    // Default method for getting album artwork (can be overridden)
    public function getAlbumArt($trackInfo) {
        return isset($trackInfo['album_art']) ? $trackInfo['album_art'] : null;
    }
}