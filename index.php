<?php

require_once 'providers/ProviderManager.php';
require_once 'Template.php';

// Initialize provider manager and template engine
$providerManager = new ProviderManager();
$musicProviders = $providerManager->getMainProviders(); // Only show main providers on home screen
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
function showProviderSelection($trackName, $artistName, $trackInfo = null, $originalUrl = null) {
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
        'albumArt' => isset($trackInfo['album_art']) ? $trackInfo['album_art'] : null,
        'originalUrl' => $originalUrl
    ]);
}


// Handle AJAX API request for track info
if (isset($_GET['api']) && $_GET['api'] === 'track-info') {
    header('Content-Type: application/json');
    
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['url'])) {
        echo json_encode(['success' => false, 'error' => 'URL is required']);
        exit();
    }
    
    $apiUrl = $input['url'];
    
    // Remove protocol if present
    if (preg_match('/^https?:\/\/(.+)/', $apiUrl, $matches)) {
        $apiUrl = $matches[1];
    }
    
    // Parse the URL
    $parsedTrack = $providerManager->parseUrl($apiUrl);
    
    if (!$parsedTrack) {
        echo json_encode(['success' => false, 'error' => 'Unsupported URL format']);
        exit();
    }
    
    // Get track information
    $trackInfo = $providerManager->getTrackInfo($parsedTrack['platform'], $parsedTrack['data']);
    
    if (!$trackInfo || empty($trackInfo['name']) || empty($trackInfo['artists'][0]['name'])) {
        echo json_encode(['success' => false, 'error' => 'Unable to fetch track information']);
        exit();
    }
    
    $trackName = $trackInfo['name'];
    $artistName = $trackInfo['artists'][0]['name'];
    
    // Check if user has a preference
    $userPreference = getUserPreference();
    
    $response = [
        'success' => true,
        'track' => [
            'name' => $trackName,
            'artist' => $artistName,
            'albumArt' => isset($trackInfo['album_art']) ? $trackInfo['album_art'] : null,
            'providers' => []
        ],
        'hasPreference' => false,
        'redirectUrl' => null
    ];
    
    // Build providers list
    foreach ($musicProviders as $provider => $baseUrl) {
        $response['track']['providers'][] = [
            'name' => $provider,
            'displayName' => ucfirst($provider),
            'url' => $providerManager->getSearchUrl($provider, $trackName, $artistName)
        ];
    }
    
    // If user has preference, set redirect URL
    if ($userPreference && isset($musicProviders[$userPreference])) {
        $response['hasPreference'] = true;
        $response['redirectUrl'] = $providerManager->getSearchUrl($userPreference, $trackName, $artistName);
    }
    
    echo json_encode($response);
    exit();
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

// If no URL path, show homepage
if (empty($musicUrl) || $musicUrl === '') {
    $template->display('homepage', []);
    exit();
}

// Handle URLs that start with http:// or https://
if (preg_match('/^https?:\/\/(.+)/', $musicUrl, $matches)) {
    $musicUrl = $matches[1];
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
            showProviderSelection($trackName, $artistName, $trackInfo, $musicUrl);
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
                showProviderSelection($trackName, $artistName, $trackInfo, $trackId);
                exit();
            }
        }
    }
    
    echo "Invalid or unsupported music URL format. Supported platforms: Spotify, YouTube Music, Apple Music, Deezer, Tidal, SoundCloud";
    exit();
}
?>