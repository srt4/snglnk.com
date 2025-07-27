<?php

require_once 'providers/ProviderManager.php';

// Initialize provider manager
$providerManager = new ProviderManager();
$musicProviders = $providerManager->getSearchUrls();

// Function to get user's preferred music provider from cookie
function getUserPreference() {
    return isset($_COOKIE['music_provider']) ? $_COOKIE['music_provider'] : null;
}

// Function to set user's music provider preference
function setUserPreference($provider) {
    setcookie('music_provider', $provider, time() + (365 * 24 * 60 * 60), '/'); // 1 year
}

// Function to show provider selection page
function showProviderSelection($trackName, $artistName) {
    global $musicProviders, $providerManager;
    
    $pageTitle = htmlspecialchars($trackName . ' by ' . $artistName . ' - snglnk');
    $description = 'Listen to "' . htmlspecialchars($trackName) . '" by ' . htmlspecialchars($artistName) . ' on your preferred music platform';
    
    echo '<!DOCTYPE html>
<html>
<head>
    <title>' . $pageTitle . '</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview -->
    <meta property="og:type" content="music.song">
    <meta property="og:title" content="' . htmlspecialchars($trackName) . '">
    <meta property="og:description" content="by ' . htmlspecialchars($artistName) . ' • Choose your music app">
    <meta property="og:image" content="https://snglnk.com/og-image.png">
    <meta property="og:url" content="' . htmlspecialchars($_SERVER['REQUEST_URI']) . '">
    <meta property="og:site_name" content="snglnk">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="' . htmlspecialchars($trackName) . '">
    <meta name="twitter:description" content="by ' . htmlspecialchars($artistName) . ' • Choose your music app">
    
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; }
        .track-info { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .provider { padding: 15px; background: #007acc; color: white; text-decoration: none; border-radius: 8px; transition: background 0.2s; }
        .provider:hover { background: #005999; }
        .remember { margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <h1>🎵 snglnk</h1>
    <div class="track-info">
        <h2>' . htmlspecialchars($trackName) . '</h2>
        <p>by ' . htmlspecialchars($artistName) . '</p>
    </div>
    <p>Choose your music provider:</p>
    <div class="providers">';
    
    foreach ($musicProviders as $provider => $url) {
        $redirectUrl = $providerManager->getSearchUrl($provider, $trackName, $artistName);
        $providerName = ucfirst($provider);
        echo '<a href="' . $redirectUrl . '" class="provider" onclick="setPreference(\'' . $provider . '\')">' . $providerName . '</a>';
    }
    
    echo '</div>
    <div class="remember">
        <label><input type="checkbox" id="remember" checked> Remember my choice</label>
    </div>
    
    <script>
    function setPreference(provider) {
        if (document.getElementById("remember").checked) {
            document.cookie = "music_provider=" + provider + "; max-age=" + (365*24*60*60) + "; path=/";
        }
    }
    </script>
</body>
</html>';
}

// Handle preference setting
if (isset($_GET['set_provider']) && isset($musicProviders[$_GET['set_provider']])) {
    setUserPreference($_GET['set_provider']);
    echo "Preference set to " . $_GET['set_provider'];
    exit();
}

// Handle preference reset
if (isset($_GET['reset'])) {
    setcookie('music_provider', '', time() - 3600, '/'); // Delete cookie
    echo "Music provider preference cleared! Next link will show provider selection.";
    exit();
}

// Get the music URL from the URL path
$fullUrl = $_SERVER['REQUEST_URI'];
$musicUrl = ltrim($fullUrl, '/');
$musicUrl = urldecode($musicUrl);

// Remove debug parameter if present
$musicUrl = preg_replace('/[&?]debug=1/', '', $musicUrl);

// Handle URLs that start with http:// or https://
if (preg_match('/^https?:\/\/(.+)/', $musicUrl, $matches)) {
    $musicUrl = $matches[1];
}

// Debug URL parsing
if (isset($_GET['debug_parse'])) {
    echo "Music URL: " . htmlspecialchars($musicUrl) . "<br>";
    $parsedTrack = $providerManager->parseUrl($musicUrl);
    echo "Parsed result: " . print_r($parsedTrack, true) . "<br>";
    exit();
}

// Parse the music URL to detect platform and extract track info
$parsedTrack = $providerManager->parseUrl($musicUrl);

if ($parsedTrack) {
    // Get track information from the detected platform
    $trackInfo = $providerManager->getTrackInfo($parsedTrack['platform'], $parsedTrack['data']);

    if ($trackInfo && !empty($trackInfo['name']) && !empty($trackInfo['artists'][0]['name'])) {
        $trackName = $trackInfo['name'];
        $artistName = $trackInfo['artists'][0]['name'];

        // Get user's preferred music provider
        $userPreference = getUserPreference();
        
        if ($userPreference && isset($musicProviders[$userPreference])) {
            // Redirect to user's preferred provider
            $redirectUrl = $providerManager->getSearchUrl($userPreference, $trackName, $artistName);
            header("Location: $redirectUrl");
            exit();
        } else {
            // No preference set - show provider selection page
            showProviderSelection($trackName, $artistName);
            exit();
        }
    } else {
        echo "Unable to fetch track information from " . $parsedTrack['platform'] . ".";
        exit();
    }
} else {
    // Try legacy Spotify track ID format for backwards compatibility
    if (preg_match('/^([a-zA-Z0-9]+)(.*)$/', $musicUrl, $matches)) {
        $trackId = $matches[1];
        $spotifyProvider = new SpotifyProvider();
        $trackInfo = $spotifyProvider->getTrackInfo(['id' => $trackId]);
        
        if ($trackInfo && !empty($trackInfo['name']) && !empty($trackInfo['artists'][0]['name'])) {
            $trackName = $trackInfo['name'];
            $artistName = $trackInfo['artists'][0]['name'];

            $userPreference = getUserPreference();
            
            if ($userPreference && isset($musicProviders[$userPreference])) {
                $redirectUrl = $providerManager->getSearchUrl($userPreference, $trackName, $artistName);
                header("Location: $redirectUrl");
                exit();
            } else {
                showProviderSelection($trackName, $artistName);
                exit();
            }
        }
    }
    
    echo "Invalid or unsupported music URL format. Supported platforms: Spotify, YouTube Music, Apple Music, Deezer, Tidal, SoundCloud";
    exit();
}
?>