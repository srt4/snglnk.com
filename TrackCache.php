<?php

class TrackCache {
    private $db;
    private $dbPath;
    
    public function __construct($dbPath = 'cache/tracks.db') {
        // Create cache directory if it doesn't exist
        $cacheDir = dirname($dbPath);
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0755, true)) {
                // Fallback to system temp directory
                $dbPath = sys_get_temp_dir() . '/snglnk_tracks.db';
            }
        }
        
        try {
            $this->db = new SQLite3($dbPath);
            $this->dbPath = $dbPath;
            $this->initDatabase();
        } catch (Exception $e) {
            // Fallback to system temp directory
            $tempPath = sys_get_temp_dir() . '/snglnk_tracks.db';
            try {
                $this->db = new SQLite3($tempPath);
                $this->dbPath = $tempPath;
                $this->initDatabase();
            } catch (Exception $e2) {
                // If all else fails, disable caching
                $this->db = null;
                $this->dbPath = null;
            }
        }
    }
    
    private function initDatabase() {
        if (!$this->db) return;
        
        $sql = "CREATE TABLE IF NOT EXISTS tracks (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            platform TEXT NOT NULL,
            track_id TEXT NOT NULL,
            name TEXT NOT NULL,
            artist TEXT NOT NULL,
            album_art TEXT,
            cached_at INTEGER NOT NULL,
            UNIQUE(platform, track_id)
        )";
        
        $this->db->exec($sql);
        
        // Create index for faster lookups
        $this->db->exec("CREATE INDEX IF NOT EXISTS idx_platform_track ON tracks(platform, track_id)");
    }
    
    public function cleanUrl($url) {
        // Remove common tracking parameters
        $trackingParams = ['si', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'utm_term', 'fbclid', 'gclid'];
        
        $parsed = parse_url($url);
        if (!isset($parsed['query'])) {
            return $url;
        }
        
        parse_str($parsed['query'], $params);
        
        // Remove tracking parameters
        foreach ($trackingParams as $param) {
            unset($params[$param]);
        }
        
        // Rebuild URL
        $cleanUrl = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['path'])) {
            $cleanUrl .= $parsed['path'];
        }
        
        if (!empty($params)) {
            $cleanUrl .= '?' . http_build_query($params);
        }
        
        if (isset($parsed['fragment'])) {
            $cleanUrl .= '#' . $parsed['fragment'];
        }
        
        return $cleanUrl;
    }
    
    public function getTrackKey($platform, $data) {
        // Create a consistent key from track data
        if (isset($data['id'])) {
            return $data['id'];
        } elseif (isset($data['track_id'])) {
            return $data['track_id'];
        } elseif (isset($data['album_id']) && isset($data['track_id'])) {
            return $data['album_id'] . ':' . $data['track_id'];
        }
        
        // Fallback: serialize the data
        return md5(serialize($data));
    }
    
    public function get($platform, $trackKey) {
        if (!$this->db) return null;
        
        try {
            $stmt = $this->db->prepare("SELECT name, artist, album_art, cached_at FROM tracks WHERE platform = ? AND track_id = ?");
            $stmt->bindValue(1, $platform, SQLITE3_TEXT);
            $stmt->bindValue(2, $trackKey, SQLITE3_TEXT);
            
            $result = $stmt->execute();
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            if ($row) {
                // Check if cache is still fresh (24 hours)
                if (time() - $row['cached_at'] < 86400) {
                    return [
                        'name' => $row['name'],
                        'artists' => [['name' => $row['artist']]],
                        'album_art' => $row['album_art']
                    ];
                } else {
                    // Cache expired, delete it
                    $this->delete($platform, $trackKey);
                }
            }
        } catch (Exception $e) {
            // Ignore cache errors
        }
        
        return null;
    }
    
    public function set($platform, $trackKey, $trackInfo) {
        if (!$this->db || !isset($trackInfo['name']) || !isset($trackInfo['artists'][0]['name'])) {
            return false;
        }
        
        try {
            $stmt = $this->db->prepare("INSERT OR REPLACE INTO tracks (platform, track_id, name, artist, album_art, cached_at) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bindValue(1, $platform, SQLITE3_TEXT);
            $stmt->bindValue(2, $trackKey, SQLITE3_TEXT);
            $stmt->bindValue(3, $trackInfo['name'], SQLITE3_TEXT);
            $stmt->bindValue(4, $trackInfo['artists'][0]['name'], SQLITE3_TEXT);
            $stmt->bindValue(5, $trackInfo['album_art'] ?? null, SQLITE3_TEXT);
            $stmt->bindValue(6, time(), SQLITE3_INTEGER);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function delete($platform, $trackKey) {
        if (!$this->db) return false;
        
        try {
            $stmt = $this->db->prepare("DELETE FROM tracks WHERE platform = ? AND track_id = ?");
            $stmt->bindValue(1, $platform, SQLITE3_TEXT);
            $stmt->bindValue(2, $trackKey, SQLITE3_TEXT);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function cleanup() {
        if (!$this->db) return false;
        
        try {
            // Remove entries older than 7 days
            $weekAgo = time() - (7 * 24 * 60 * 60);
            
            $stmt = $this->db->prepare("DELETE FROM tracks WHERE cached_at < ?");
            $stmt->bindValue(1, $weekAgo, SQLITE3_INTEGER);
            
            return $stmt->execute();
        } catch (Exception $e) {
            return false;
        }
    }
    
    public function getStats() {
        if (!$this->db) {
            return [
                'total_cached' => 0,
                'db_size' => 0,
                'cache_enabled' => false
            ];
        }
        
        try {
            $result = $this->db->query("SELECT COUNT(*) as total FROM tracks");
            $row = $result->fetchArray(SQLITE3_ASSOC);
            
            $dbSize = 0;
            if ($this->dbPath && file_exists($this->dbPath)) {
                $dbSize = filesize($this->dbPath);
            }
            
            // Get all cached entries
            $entries = [];
            $allResult = $this->db->query("SELECT platform, track_id, name, artist, album_art, cached_at FROM tracks ORDER BY cached_at DESC");
            while ($entryRow = $allResult->fetchArray(SQLITE3_ASSOC)) {
                $entries[] = [
                    'platform' => $entryRow['platform'],
                    'track_id' => $entryRow['track_id'],
                    'name' => $entryRow['name'],
                    'artist' => $entryRow['artist'],
                    'album_art' => $entryRow['album_art'],
                    'cached_at' => $entryRow['cached_at'],
                    'cached_ago' => time() - $entryRow['cached_at'] . ' seconds ago'
                ];
            }
            
            return [
                'total_cached' => $row['total'],
                'db_size' => $dbSize,
                'cache_enabled' => true,
                'db_path' => $this->dbPath,
                'entries' => $entries
            ];
        } catch (Exception $e) {
            return [
                'total_cached' => 0,
                'db_size' => 0,
                'cache_enabled' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}