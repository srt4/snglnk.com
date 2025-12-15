<?php

require_once 'MusicProvider.php';

class TidalProvider extends MusicProvider
{

    public function __construct()
    {
        parent::__construct('tidal', 'https://listen.tidal.com/search?q=');
    }

    public function parseUrl($url)
    {
        if (preg_match('/tidal\.com\/browse\/track\/(\d+)/', $url, $matches)) {
            return ['id' => $matches[1]];
        }
        return null;
    }

    public function getTrackInfo($data)
    {
        $trackId = $data['id'];

        // Try to scrape the Tidal page for track info
        $tidalUrl = "https://tidal.com/browse/track/{$trackId}";

        // Debug output
        if (isset($_GET['debug_tidal'])) {
            echo "Tidal URL: " . $tidalUrl . "<br>";
            $html = ProviderManager::fetchUrl($tidalUrl);
            echo "HTML length: " . strlen($html) . "<br>";
            if (preg_match('/<title>([^<]+)<\/title>/', $html, $matches)) {
                echo "Title found: " . htmlspecialchars($matches[1]) . "<br>";
            } else {
                echo "No title found<br>";
            }
            exit();
        }

        $html = ProviderManager::fetchUrl($tidalUrl);

        if ($html) {
            // Try to extract track name and artist from page title
            if (preg_match('/<title>([^<]+)<\/title>/', $html, $matches)) {
                $title = html_entity_decode($matches[1]);

                // Tidal titles are in format "Track Name by Artist Name on TIDAL"
                if (preg_match('/^(.+?)\s+by\s+(.+?)\s+on\s+TIDAL/', $title, $parts)) {
                    return [
                        'name' => trim($parts[1]),
                        'artists' => [['name' => trim($parts[2])]]
                    ];
                }
                // Fallback: try "Track - Artist" format
                elseif (preg_match('/^(.+?)\s*-\s*(.+?)\s*\|/', $title, $parts)) {
                    return [
                        'name' => trim($parts[1]),
                        'artists' => [['name' => trim($parts[2])]]
                    ];
                }
            }

            // Try to find JSON-LD structured data
            if (preg_match('/<script type="application\/ld\+json">([^<]+)<\/script>/', $html, $matches)) {
                $jsonData = json_decode($matches[1], true);
                if ($jsonData && isset($jsonData['name']) && isset($jsonData['byArtist']['name'])) {
                    return [
                        'name' => $jsonData['name'],
                        'artists' => [['name' => $jsonData['byArtist']['name']]]
                    ];
                }
            }
        }

        // Fallback - at least show the track ID
        return [
            'name' => "Tidal Track #{$trackId}",
            'artists' => [['name' => 'Unknown Artist']]
        ];
    }

    public function getSearchUrl($trackName, $artistName)
    {
        return $this->baseUrl . urlencode($trackName . ' ' . $artistName);
    }
}