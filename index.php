<?php

require_once 'providers/ProviderManager.php';
require_once 'Template.php';

// Initialize provider manager and template engine
$providerManager = new ProviderManager();
$musicProviders = $providerManager->getSearchUrls();
$template = new Template();

// Function to get user's preferred music provider from cookie
function getUserPreference() {
    return isset($_COOKIE['music_provider']) ? $_COOKIE['music_provider'] : null;
}

// Function to set user's music provider preference
function setUserPreference($provider) {
    setcookie('music_provider', $provider, time() + (365 * 24 * 60 * 60), '/'); // 1 year
}

// Function to show provider selection page
function showProviderSelection($trackName, $artistName, $trackInfo = null) {
    global $musicProviders, $providerManager, $template;
    
    // Prepare provider data for template
    $providers = [];
    foreach ($musicProviders as $provider => $url) {
        $providers[] = [
            'name' => $provider,
            'url' => $providerManager->getSearchUrl($provider, $trackName, $artistName)
        ];
    }
    
    // Render template
    $template->display('provider-selection', [
        'trackName' => $trackName,
        'artistName' => $artistName,
        'providers' => $providers,
        'albumArt' => isset($trackInfo['album_art']) ? $trackInfo['album_art'] : null
    ]);
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
            showProviderSelection($trackName, $artistName, $trackInfo);
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
                showProviderSelection($trackName, $artistName, $trackInfo);
                exit();
            }
        }
    }
    
    echo "Invalid or unsupported music URL format. Supported platforms: Spotify, YouTube Music, Apple Music, Deezer, Tidal, SoundCloud";
    exit();
}
?>