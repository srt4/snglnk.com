<?php

require_once '../providers/ProviderManager.php';

header('Content-Type: application/json');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

$url = $input['url'];

// Remove protocol if present
if (preg_match('/^https?:\/\/(.+)/', $url, $matches)) {
    $url = $matches[1];
}

$providerManager = new ProviderManager();

// Parse the URL
$parsedTrack = $providerManager->parseUrl($url);

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
$userPreference = isset($_COOKIE['music_provider']) ? $_COOKIE['music_provider'] : null;
$mainProviders = $providerManager->getMainProviders();

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
foreach ($mainProviders as $provider => $baseUrl) {
    $response['track']['providers'][] = [
        'name' => $provider,
        'displayName' => ucfirst($provider),
        'url' => $providerManager->getSearchUrl($provider, $trackName, $artistName)
    ];
}

// If user has preference, set redirect URL
if ($userPreference && isset($mainProviders[$userPreference])) {
    $response['hasPreference'] = true;
    $response['redirectUrl'] = $providerManager->getSearchUrl($userPreference, $trackName, $artistName);
}

echo json_encode($response);
?>