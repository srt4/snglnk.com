<?php
$startTime = microtime(true);

$requireStart = microtime(true);
require_once 'providers/ProviderManager.php';
require_once 'Template.php';
$requireEnd = microtime(true);

// Initialize provider manager and template engine
$initStart = microtime(true);
$providerManager = new ProviderManager();
$musicProviders = $providerManager->getMainProviders(); // Only show main providers on home screen
$template = new Template();
$initEnd = microtime(true);

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


// Handle AJAX API request for lazy content
if (isset($_GET['api']) && $_GET['api'] === 'lazy-content') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
        exit();
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    $trackName = $input['trackName'] ?? '';
    $artistName = $input['artistName'] ?? '';
    $albumArt = $input['albumArt'] ?? null;
    
    // Generate the provider content
    $providers = [];
    foreach ($musicProviders as $provider => $baseUrl) {
        $providers[] = [
            'name' => $provider,
            'displayName' => ucfirst($provider),
            'url' => $providerManager->getSearchUrl($provider, $trackName, $artistName)
        ];
    }
    
    // Return HTML content using template
    $template->display('lazy-content', [
        'trackName' => $trackName,
        'artistName' => $artistName,
        'albumArt' => $albumArt,
        'providers' => $providers
    ]);
    
    exit();
}

// Handle AJAX API request for track info
if (isset($_GET['api']) && $_GET['api'] === 'track-info') {
    $ajaxStartTime = microtime(true);
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
    $ajaxParseStart = microtime(true);
    $parsedTrack = $providerManager->parseUrl($apiUrl);
    $ajaxParseEnd = microtime(true);
    
    if (!$parsedTrack) {
        echo json_encode(['success' => false, 'error' => 'Unsupported URL format']);
        exit();
    }
    
    // Get track information
    $ajaxApiStart = microtime(true);
    $trackInfo = $providerManager->getTrackInfo($parsedTrack['platform'], $parsedTrack['data']);
    $ajaxApiEnd = microtime(true);
    
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
    
    // Add performance metrics
    $ajaxTotalTime = microtime(true) - $ajaxStartTime;
    $ajaxParseTime = ($ajaxParseEnd - $ajaxParseStart) * 1000;
    $ajaxApiTime = ($ajaxApiEnd - $ajaxApiStart) * 1000;
    $response['perf'] = [
        'total' => round($ajaxTotalTime * 1000, 2),
        'parse' => round($ajaxParseTime, 2),
        'api' => round($ajaxApiTime, 2),
        'platform' => $parsedTrack['platform']
    ];
    
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
$urlParseStart = microtime(true);
$fullUrl = $_SERVER['REQUEST_URI'];
$musicUrl = ltrim($fullUrl, '/');
$musicUrl = urldecode($musicUrl);

// Remove debug parameter if present
$musicUrl = preg_replace('/[&?]debug=1/', '', $musicUrl);
$urlParseEnd = microtime(true);

// If no URL path, show homepage
if (empty($musicUrl) || $musicUrl === '') {
    $template->display('homepage', []);
    exit();
}

// Function to detect social media crawlers/bots
function isSocialBot() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $botPatterns = [
        'WhatsApp',
        'facebookexternalhit',
        'Twitterbot',
        'LinkedInBot',
        'TelegramBot',
        'SkypeUriPreview',
        'Slackbot',
        'RedditBot',
        'discordbot',
        'google',  // Google for link previews
        'crawler',
        'spider',
        'bot'
    ];
    
    foreach ($botPatterns as $pattern) {
        if (stripos($userAgent, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

// Handle URLs that start with http:// or https://
if (preg_match('/^https?:\/\/(.+)/', $musicUrl, $matches)) {
    $musicUrl = $matches[1];
}

// Check if it's a bot vs real user FIRST (before expensive operations)
if (isSocialBot()) {
    // For bots: Do full processing for link previews
    $parseStartTime = microtime(true);
    $parsedTrack = $providerManager->parseUrl($musicUrl);
    $parseEndTime = microtime(true);

    if ($parsedTrack) {
        $apiStartTime = microtime(true);
        $trackInfo = $providerManager->getTrackInfo($parsedTrack['platform'], $parsedTrack['data']);
        $apiEndTime = microtime(true);

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
                // Show full page for bots
                $totalTime = microtime(true) - $startTime;
                $parseTime = ($parseEndTime - $parseStartTime) * 1000;
                $apiTime = ($apiEndTime - $apiStartTime) * 1000;
                echo "<!-- PERF BOT: Total=" . round($totalTime * 1000, 2) . "ms Parse=" . round($parseTime, 2) . "ms API=" . round($apiTime, 2) . "ms Platform=" . $parsedTrack['platform'] . " -->";
                showProviderSelection($trackName, $artistName, $trackInfo, $musicUrl);
                exit();
            }
        } else {
            echo "Unable to fetch track information from " . $parsedTrack['platform'] . ".";
            exit();
        }
    } else {
        echo "Invalid or unsupported music URL format. Supported platforms: Spotify, YouTube Music, Apple Music, Deezer, Tidal, SoundCloud";
        exit();
    }
} else {
    // For real users: Show lazy loading version with NO expensive operations
    $totalTime = microtime(true) - $startTime;
    $requireTime = ($requireEnd - $requireStart) * 1000;
    $initTime = ($initEnd - $initStart) * 1000;
    $urlParseTime = ($urlParseEnd - $urlParseStart) * 1000;
    echo "<!-- PERF USER: Total=" . round($totalTime * 1000, 2) . "ms Requires=" . round($requireTime, 2) . "ms Init=" . round($initTime, 2) . "ms UrlParse=" . round($urlParseTime, 2) . "ms -->";
    $template->display('lazy-loading', [
        'originalUrl' => $musicUrl
    ]);
    exit();
}
?>