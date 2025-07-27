<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($trackName . ' by ' . $artistName . ' - snglnk') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview -->
    <meta property="og:type" content="music.song">
    <meta property="og:title" content="<?= htmlspecialchars($trackName) ?>">
    <meta property="og:description" content="by <?= htmlspecialchars($artistName) ?> • Choose your music app">
    <meta property="og:image" content="https://snglnk.com/og-image.png">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <meta property="og:site_name" content="snglnk">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($trackName) ?>">
    <meta name="twitter:description" content="by <?= htmlspecialchars($artistName) ?> • Choose your music app">
    
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
    <h1>snglnk</h1>
    <div class="track-info">
        <h2><?= htmlspecialchars($trackName) ?></h2>
        <p>by <?= htmlspecialchars($artistName) ?></p>
    </div>
    <p>Choose your music provider:</p>
    <div class="providers">
        <?php foreach ($providers as $provider): ?>
            <a href="<?= $provider['url'] ?>" 
               class="provider" 
               onclick="setPreference('<?= $provider['name'] ?>')">
                <?= ucfirst($provider['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
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
</html>