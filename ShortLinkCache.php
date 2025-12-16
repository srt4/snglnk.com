<?php
class ShortLinkCache
{
    private $db;

    public function __construct($dbPath = 'cache/shortlinks.db')
    {
        // Create cache directory if it doesn't exist
        $cacheDir = dirname($dbPath);
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                $dbPath = sys_get_temp_dir() . '/snglnk_shortlinks.db';
            }
        }

        try {
            $this->db = new SQLite3($dbPath);
            $this->db->exec("CREATE TABLE IF NOT EXISTS clicks (
                short_code TEXT PRIMARY KEY,
                original_url TEXT,
                track_name TEXT,
                cnt INTEGER DEFAULT 0,
                last_clicked INTEGER
            )");
        } catch (Exception $e) {
            $this->db = null;
        }
    }

    public function createShortLink($originalUrl, $trackName = null, $artistName = null, $albumArt = null)
    {
        $shortCode = $this->generateShortCode($originalUrl);
        if ($shortCode && $this->db) {
            try {
                $stmt = $this->db->prepare("INSERT OR IGNORE INTO clicks (short_code, original_url, track_name, cnt, last_clicked) VALUES (?, ?, ?, 0, ?)");
                $stmt->bindValue(1, $shortCode, SQLITE3_TEXT);
                $stmt->bindValue(2, $originalUrl, SQLITE3_TEXT);
                $stmt->bindValue(3, $trackName ? "$trackName - $artistName" : null, SQLITE3_TEXT);
                $stmt->bindValue(4, time(), SQLITE3_INTEGER);
                $stmt->execute();
            } catch (Exception $e) {
            }
        }
        return $shortCode;
    }

    public function generateShortCode($originalUrl)
    {
        // Extract track ID from the URL
        $trackId = $this->extractTrackId($originalUrl);
        if ($trackId) {
            $provider = $this->getProviderPrefix($originalUrl);
            return $provider . '/' . $trackId;
        }

        return null; // Can't create short link without provider track ID
    }

    private function getProviderPrefix($url)
    {
        if (strpos($url, 'spotify.com') !== false)
            return 's';
        if (strpos($url, 'youtube.com') !== false)
            return 'y';
        if (strpos($url, 'apple.com') !== false)
            return 'a';

        return null; // Only support main providers
    }

    private function extractTrackId($url)
    {
        // Remove protocol if present
        $url = preg_replace('/^https?:\/\//', '', $url);

        // Spotify: open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh
        if (preg_match('/spotify\.com\/track\/([a-zA-Z0-9]+)/', $url, $matches)) {
            return $matches[1];
        }

        // YouTube Music: music.youtube.com/watch?v=dQw4w9WgXcQ
        if (preg_match('/music\.youtube\.com\/watch\?v=([a-zA-Z0-9_-]+)/', $url, $matches)) {
            return $matches[1];
        }

        // Apple Music: music.apple.com/us/album/song-name/1234567890?i=0987654321
        if (preg_match('/music\.apple\.com\/.*\/([0-9]+)\?i=([0-9]+)/', $url, $matches)) {
            return $matches[2]; // Use the track ID part
        }

        // Apple Music album format: music.apple.com/us/album/album-name/1234567890
        if (preg_match('/music\.apple\.com\/.*\/([0-9]+)$/', $url, $matches)) {
            return $matches[1];
        }

        return null;
    }

    public function getByCode($shortCode)
    {
        // Reconstruct original URL from provider short code
        return $this->reconstructOriginalUrl($shortCode);
    }

    private function reconstructOriginalUrl($shortCode)
    {
        if (!preg_match('/^([say])\/(.+)$/', $shortCode, $matches)) {
            return null;
        }

        $provider = $matches[1];
        $trackId = $matches[2];

        switch ($provider) {
            case 's': // Spotify
                return ['original_url' => "open.spotify.com/track/{$trackId}"];
            case 'y': // YouTube
                return ['original_url' => "music.youtube.com/watch?v={$trackId}"];
            case 'a': // Apple Music
                return ['original_url' => "music.apple.com/us/album/1/{$trackId}?i={$trackId}"];
            default:
                return null;
        }
    }

    public function incrementClicks($shortCode)
    {
        if (!$this->db)
            return false;

        try {
            $stmt = $this->db->prepare("UPDATE clicks SET cnt = cnt + 1, last_clicked = ? WHERE short_code = ?");
            $stmt->bindValue(1, time(), SQLITE3_INTEGER);
            $stmt->bindValue(2, $shortCode, SQLITE3_TEXT);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getStats()
    {
        if (!$this->db)
            return ['error' => 'No database connection'];

        try {
            $result = $this->db->query("SELECT COUNT(*) as total FROM clicks");
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $totalLinks = $row['total'];

            $result = $this->db->query("SELECT SUM(cnt) as total_clicks FROM clicks");
            $row = $result->fetchArray(SQLITE3_ASSOC);
            $totalClicks = $row['total_clicks'] ?: 0;

            return [
                'shortlinks_enabled' => true,
                'tracking_enabled' => true,
                'total_links' => $totalLinks,
                'total_clicks' => $totalClicks,
                'format' => '{provider_char}/{provider_id}'
            ];
        } catch (Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }

    public function getTopLinks($limit = 10)
    {
        if (!$this->db)
            return [];

        try {
            $stmt = $this->db->prepare("SELECT short_code, original_url, track_name, cnt, last_clicked FROM clicks ORDER BY cnt DESC LIMIT ?");
            $stmt->bindValue(1, $limit, SQLITE3_INTEGER);
            $result = $stmt->execute();

            $links = [];
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $links[] = [
                    'short_code' => $row['short_code'],
                    'original_url' => $row['original_url'],
                    'track_name' => $row['track_name'],
                    'clicks' => $row['cnt'],
                    'last_clicked' => $row['last_clicked'] ? date('M j, Y g:ia', $row['last_clicked']) : 'Never'
                ];
            }
            return $links;
        } catch (Exception $e) {
            return [];
        }
    }
}