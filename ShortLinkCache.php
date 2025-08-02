<?php

class ShortLinkCache {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = 'cache/shortlinks.db') {
        // Create cache directory if it doesn't exist
        $cacheDir = dirname($dbPath);
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                // Fallback to system temp directory
                $dbPath = sys_get_temp_dir() . '/snglnk_shortlinks.db';
            }
        }
        
        try {
            $this->db = new SQLite3($dbPath);
            $this->dbPath = $dbPath;
            $this->initDatabase();
        } catch (Exception $e) {
            // Fallback to system temp directory
            $tempPath = sys_get_temp_dir() . '/snglnk_shortlinks.db';
            try {
                $this->db = new SQLite3($tempPath);
                $this->dbPath = $tempPath;
                $this->initDatabase();
            } catch (Exception $e2) {
                // If all else fails, disable short links
                $this->db = null;
                $this->dbPath = null;
            }
        }
    }
    
    private function initDatabase() {
        if (!$this->db) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS shortlinks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            short_code TEXT NOT NULL UNIQUE,
            original_url TEXT NOT NULL,
            track_name TEXT,
            artist_name TEXT,
            album_art TEXT,
            clicks INTEGER DEFAULT 0,
            created_at INTEGER NOT NULL,
            last_accessed INTEGER,
            UNIQUE(short_code)
        )";
        
        $this->db->exec($sql);
        
        // Create index for faster lookups
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_short_code ON shortlinks(short_code)");
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_created_at ON shortlinks(created_at)");
    }
    
    public function generateShortCode($length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = '';
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $code;
    }
    
    public function generatePrefixedShortCode($originalUrl) {
        // Extract track ID from the URL
        $trackId = $this->extractTrackId($originalUrl);
        if ($trackId) {
            $provider = $this->getProviderPrefix($originalUrl);
            return $provider . '/' . $trackId;
        }
        
        // Fallback to random code if we can't extract ID
        $prefixes = ['s', 'a', 'y']; // snglnk letters
        $prefix = $prefixes[array_rand($prefixes)];
        $id = $this->generateShortCode(5);
        return $prefix . '/' . $id;
    }
    
    private function getProviderPrefix($url) {
        if (strpos($url, 'spotify.com') !== false) return 's';
        if (strpos($url, 'youtube.com') !== false) return 'y';  
        if (strpos($url, 'apple.com') !== false) return 'a';
        
        // Default fallback
        $prefixes = ['s', 'a', 'y'];
        return $prefixes[array_rand($prefixes)];
    }
    
    private function extractTrackId($url) {
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
    
    
    public function createShortLink($originalUrl, $trackName = null, $artistName = null, $albumArt = null) {
        if (!$this->db) return null;
        
        // Skip cache check for now to test new track ID extraction
        // $existing = $this->getByUrl($originalUrl);
        // if ($existing) {
        //     return $existing['short_code'];
        // }
        
        $attempts = 0;
        $maxAttempts = 10;
        
        while ($attempts < $maxAttempts) {
            $shortCode = $this->generatePrefixedShortCode($originalUrl);
            
            try {
                $stmt = $this->db->prepare("INSERT INTO shortlinks (short_code, original_url, track_name, artist_name, album_art, created_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bindValue(1, $shortCode, SQLITE3_TEXT);
                $stmt->bindValue(2, $originalUrl, SQLITE3_TEXT);
                $stmt->bindValue(3, $trackName, SQLITE3_TEXT);
                $stmt->bindValue(4, $artistName, SQLITE3_TEXT);
                $stmt->bindValue(5, $albumArt, SQLITE3_TEXT);
                $stmt->bindValue(6, time(), SQLITE3_INTEGER);
                
                if ($stmt->execute()) {
                    return $shortCode;
                }
            } catch (Exception $e) {
                // Code already exists, try again
                $attempts++;
            }
        }
        
        return null; // Failed to generate unique code
    }
    
    public function getByCode($shortCode) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM shortlinks WHERE short_code = ?");
            $stmt->bindValue(1, $shortCode, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function getByUrl($originalUrl) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM shortlinks WHERE original_url = ?");
            $stmt->bindValue(1, $originalUrl, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            return $result->fetchArray(SQLITE3_ASSOC);
        } catch (Exception $e) {
            return null;
        }
    }
    
    public function incrementClicks($shortCode) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("UPDATE shortlinks SET clicks = clicks + 1, last_accessed = ? WHERE short_code = ?");
            $stmt->bindValue(1, time(), SQLITE3_INTEGER);
            $stmt->bindValue(2, $shortCode, SQLITE3_TEXT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getStats() {
        if (!$this->db) {
            return [
                'total_links' => 0,
                'total_clicks' => 0,
                'db_size' => 0,
                'shortlinks_enabled' => false
            ];
        }
        
        try {
            $totalResult = $this->db->query("SELECT COUNT(*) as total FROM shortlinks");
            $totalRow = $totalResult->fetchArray(SQLITE3_ASSOC);
            
            $clicksResult = $this->db->query("SELECT SUM(clicks) as total_clicks FROM shortlinks");
            $clicksRow = $clicksResult->fetchArray(SQLITE3_ASSOC);
            
            $dbSize = 0;
            if ($this->dbPath && file_exists($this->dbPath)) {
                $dbSize = filesize($this->dbPath);
            }
            
            return [
                'total_links' => $totalRow['total'],
                'total_clicks' => $clicksRow['total_clicks'] ?: 0,
                'db_size' => $dbSize,
                'shortlinks_enabled' => true,
                'db_path' => $this->dbPath
            ];
        } catch (Exception $e) {
            return [
                'total_links' => 0,
                'total_clicks' => 0,
                'db_size' => 0,
                'shortlinks_enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function getRecentLinks($limit = 10) {
        if (!$this->db) return [];
        
        try {
            $stmt = $this->db->prepare("SELECT * FROM shortlinks ORDER BY created_at DESC LIMIT ?");
            $stmt->bindValue(1, $limit, SQLITE3_INTEGER);
            
            $result = $stmt->execute();
            $links = [];
            
            while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
                $links[] = [
                    'short_code' => $row['short_code'],
                    'original_url' => $row['original_url'],
                    'track_name' => $row['track_name'],
                    'artist_name' => $row['artist_name'],
                    'album_art' => $row['album_art'],
                    'clicks' => $row['clicks'],
                    'created_at' => $row['created_at'],
                    'last_accessed' => $row['last_accessed'],
                    'created_ago' => time() - $row['created_at'] . ' seconds ago'
                ];
            }
            
            return $links;
        } catch (Exception $e) {
            return [];
        }
    }
    
    public function cleanup() {
        if (!$this->db) return false;
        
        try {
            // Remove links older than 1 year with no clicks
            $yearAgo = time() - (365 * 24 * 60 * 60);
            
            $stmt = $this->db->prepare("DELETE FROM shortlinks WHERE created_at < ? AND clicks = 0");
            $stmt->bindValue(1, $yearAgo, SQLITE3_INTEGER);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
}