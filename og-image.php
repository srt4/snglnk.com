<?php

// Dynamic Open Graph image generator with album artwork
// Usage: /og-image.php?track=TRACK&artist=ARTIST&art=ALBUM_ART_URL

// Check if GD extension is available
if (!extension_loaded('gd')) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'GD extension not available';
    exit;
}

try {
    header('Content-Type: image/png');

    // Get parameters
    $trackName = $_GET['track'] ?? 'Unknown Track';
    $artistName = $_GET['artist'] ?? 'Unknown Artist';
    $albumArtUrl = $_GET['art'] ?? null;

    // Create image
    $width = 400;
    $height = 400;
    $image = imagecreatetruecolor($width, $height);

    if (!$image) {
        throw new Exception('Failed to create image');
    }

    // Create colors
    $purple = imagecolorallocate($image, 102, 126, 234);
    $white = imagecolorallocate($image, 255, 255, 255);

    // Fill background
    imagefill($image, 0, 0, $purple);

    // Add album artwork if available
    if ($albumArtUrl && filter_var($albumArtUrl, FILTER_VALIDATE_URL)) {
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'snglnk/1.0'
            ]
        ]);
        
        $albumArt = @file_get_contents($albumArtUrl, false, $context);
        if ($albumArt) {
            $albumImage = @imagecreatefromstring($albumArt);
            if ($albumImage) {
                // Resize and position album art
                $artSize = 150;
                $artX = ($width - $artSize) / 2;
                $artY = 80;
                
                imagecopyresampled($image, $albumImage, $artX, $artY, 0, 0, $artSize, $artSize, 
                                 imagesx($albumImage), imagesy($albumImage));
                imagedestroy($albumImage);
            }
        }
    }

    // Add text with built-in fonts
    $fontSize = 3;

    // Track name
    $trackText = strlen($trackName) > 25 ? substr($trackName, 0, 22) . '...' : $trackName;
    $trackWidth = strlen($trackText) * imagefontwidth($fontSize);
    $trackX = ($width - $trackWidth) / 2;
    imagestring($image, $fontSize, $trackX, 250, $trackText, $white);

    // Artist name
    $artistText = strlen($artistName) > 30 ? substr($artistName, 0, 27) . '...' : $artistName;
    $artistWidth = strlen($artistText) * imagefontwidth(2);
    $artistX = ($width - $artistWidth) / 2;
    imagestring($image, 2, $artistX, 280, $artistText, $white);

    // snglnk branding
    $brandText = 'snglnk';
    $brandWidth = strlen($brandText) * imagefontwidth(2);
    $brandX = ($width - $brandWidth) / 2;
    imagestring($image, 2, $brandX, 340, $brandText, $white);

    // Output image
    imagepng($image);
    imagedestroy($image);
    
} catch (Exception $e) {
    header('HTTP/1.1 500 Internal Server Error');
    echo 'Error generating image: ' . $e->getMessage();
}
?>