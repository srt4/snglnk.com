<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($trackName . ' by ' . $artistName . ' - snglnk') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview -->
    <meta property="og:type" content="music.song">
    <meta property="og:title" content="<?= htmlspecialchars($trackName) ?>">
    <meta property="og:description" content="by <?= htmlspecialchars($artistName) ?> • Choose your music app">
    <?php if (isset($albumArt) && $albumArt): ?>
        <meta property="og:image" content="<?= htmlspecialchars($albumArt) ?>">
        <meta property="og:image:width" content="300">
        <meta property="og:image:height" content="300">
    <?php else: ?>
        <meta property="og:image" content="https://snglnk.com/og-image.png">
    <?php endif; ?>
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
        .provider { padding: 15px; color: white; text-decoration: none; border-radius: 8px; transition: background 0.2s; }
        .provider.spotify { background: #1db954; }
        .provider.spotify:hover { background: #1ed760; }
        .provider.youtube { background: #ff0000; }
        .provider.youtube:hover { background: #cc0000; }
        .provider.apple { background: #000000; }
        .provider.apple:hover { background: #333333; }
        .remember { margin-top: 20px; color: #666; }
        .album-art { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; }
        .input-container { margin: 20px 0; display: -webkit-flex; display: -moz-flex; display: flex; -webkit-align-items: center; -moz-align-items: center; align-items: center; gap: 10px; width: 100%; max-width: 500px; margin-left: auto; margin-right: auto; }
        .input-container > * + * { margin-left: 10px; }
        .url-input { -webkit-flex: 1; -moz-flex: 1; flex: 1; padding: 12px; font-size: 14px; border: 2px solid #ddd; border-radius: 8px; box-sizing: border-box; background: #f8f9fa; }
        .url-input:focus { outline: none; border-color: #007acc; }
        .share-btn { padding: 12px 16px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 14px; transition: background 0.2s; display: block !important; -webkit-flex-shrink: 0; -moz-flex-shrink: 0; flex-shrink: 0; min-width: 70px; }
        .share-btn:hover { background: #1e7e34; }
    </style>
</head>
<body>
    <h1>snglnk</h1>
    
    <div class="input-container">
        <input type="text" class="url-input" value="<?= htmlspecialchars($originalUrl ?? '') ?>" readonly id="musicUrl">
        <button class="share-btn" onclick="copyToClipboard()" title="Share this link">Share</button>
    </div>
    
    <div class="track-info">
        <?php if (isset($albumArt) && $albumArt): ?>
            <img src="<?= htmlspecialchars($albumArt) ?>" alt="Album Art" class="album-art">
        <?php endif; ?>
        <h2><?= htmlspecialchars($trackName) ?></h2>
        <p>by <?= htmlspecialchars($artistName) ?></p>
    </div>
    <p>Choose your music provider:</p>
    <div class="providers">
        <?php foreach ($providers as $provider): ?>
            <a href="<?= $provider['url'] ?>" 
               class="provider <?= $provider['name'] ?>" 
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
    
    function copyToClipboard() {
        const currentUrl = window.location.href;
        navigator.clipboard.writeText(currentUrl).then(() => {
            // Show temporary feedback
            const shareBtn = document.querySelector('.share-btn');
            const originalText = shareBtn.innerHTML;
            
            shareBtn.innerHTML = 'Copied!';
            shareBtn.style.background = '#28a745';
            
            setTimeout(() => {
                shareBtn.innerHTML = originalText;
                shareBtn.style.background = '#28a745';
            }, 1000);
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            // Show feedback
            const shareBtn = document.querySelector('.share-btn');
            shareBtn.innerHTML = 'Copied!';
            setTimeout(() => {
                shareBtn.innerHTML = 'Share';
            }, 1000);
        });
    }
    </script>
</body>
</html>