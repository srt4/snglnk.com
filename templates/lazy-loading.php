<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($trackName . ' by ' . $artistName . ' - snglnk') ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview (for any bots that slip through) -->
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
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; font-size: 18px; }
        .input-container { margin: 20px 0; display: -webkit-flex; display: -moz-flex; display: flex; -webkit-align-items: center; -moz-align-items: center; align-items: center; gap: 10px; width: 100%; max-width: 500px; margin-left: auto; margin-right: auto; }
        .input-container > * + * { margin-left: 10px; }
        .url-input { -webkit-flex: 1; -moz-flex: 1; flex: 1; padding: 16px; font-size: 16px; border: 2px solid #ddd; border-radius: 8px; box-sizing: border-box; background: white; }
        .url-input:focus { outline: none; border-color: #007acc; }
        .cp-btn { padding: 16px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; transition: all 0.1s ease; display: block !important; -webkit-flex-shrink: 0; -moz-flex-shrink: 0; flex-shrink: 0; min-width: 80px; transform: scale(1); }
        .cp-btn:hover { background: #1e7e34; transform: scale(1.02); }
        .cp-btn:active { transform: scale(0.98); }
        .loading { display: none; color: #666; font-size: 24px; margin: 30px 0; }
        .loading::after {
            content: '';
            animation: ellipsis 1.5s infinite;
        }
        @keyframes ellipsis {
            0% { content: ''; }
            25% { content: '●'; }
            50% { content: '●●'; }
            75% { content: '●●●'; }
            100% { content: ''; }
        }
        .content { opacity: 0; transition: opacity 0.3s ease; }
        .content.loaded { opacity: 1; }
    </style>
</head>
<body>
    <h1>snglnk</h1>
    
    <div class="input-container">
        <input type="text" class="url-input" value="<?= htmlspecialchars($originalUrl ?? '') ?>" id="musicUrl" placeholder="Paste a different music link here...">
        <button class="cp-btn" onclick="copyToClipboard()" title="Copy link">Copy</button>
    </div>
    
    <div class="loading" id="loading"></div>
    <div class="content" id="content"></div>
    
    <script>
    // Lazy load the full content via AJAX
    window.addEventListener('load', function() {
        document.getElementById('loading').style.display = 'block';
        
        fetch('/?api=lazy-content', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                url: '<?= htmlspecialchars($originalUrl ?? '') ?>',
                trackName: '<?= htmlspecialchars($trackName) ?>',
                artistName: '<?= htmlspecialchars($artistName) ?>',
                albumArt: '<?= htmlspecialchars($albumArt ?? '') ?>'
            })
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').innerHTML = html;
            document.getElementById('content').classList.add('loaded');
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('content').innerHTML = '<p>Error loading content. Please refresh the page.</p>';
            console.error('Error:', error);
        });
    });
    
    // Input handling (same as other pages)
    let debounceTimer;
    let originalUrl = document.getElementById('musicUrl').value;
    
    document.getElementById('musicUrl').addEventListener('input', function() {
        const url = this.value.trim();
        
        clearTimeout(debounceTimer);
        
        if (url !== originalUrl) {
            document.getElementById('content').style.display = 'none';
        }
        
        if (url === '') {
            history.pushState({}, '', '/');
            return;
        }
        
        debounceTimer = setTimeout(() => {
            const cleanUrl = url.replace(/^https?:\/\//, '');
            window.location.href = '/' + cleanUrl;
        }, 200);
    });
    
    function copyToClipboard() {
        const currentUrl = window.location.href;
        navigator.clipboard.writeText(currentUrl).then(() => {
            const shareBtn = document.querySelector('.cp-btn');
            const originalText = shareBtn.innerHTML;
            
            shareBtn.innerHTML = 'Copied!';
            shareBtn.style.background = '#28a745';
            
            setTimeout(() => {
                shareBtn.innerHTML = originalText;
                shareBtn.style.background = '#28a745';
            }, 1000);
        }).catch(() => {
            const textArea = document.createElement('textarea');
            textArea.value = window.location.href;
            document.body.appendChild(textArea);
            textArea.select();
            document.execCommand('copy');
            document.body.removeChild(textArea);
            
            const shareBtn = document.querySelector('.cp-btn');
            shareBtn.innerHTML = 'Copied!';
            setTimeout(() => {
                shareBtn.innerHTML = 'Copy';
            }, 1000);
        });
    }
    </script>
</body>
</html>