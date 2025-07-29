<!DOCTYPE html>
<html>
<head>
    <title>snglnk - Universal Music Link Converter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview (generic fallback) -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="snglnk - Universal Music Link Converter">
    <meta property="og:description" content="Paste any music link and choose your preferred music app">
    <meta property="og:image" content="https://snglnk.com/og-image.png">
    <meta property="og:url" content="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>">
    <meta property="og:site_name" content="snglnk">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="snglnk - Universal Music Link Converter">
    <meta name="twitter:description" content="Paste any music link and choose your preferred music app">
    
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; text-align: center; font-size: 18px; }
        @media (max-width: 768px) {
            body { margin: 0 auto; padding: 15px; }
            h1 { margin-top: 0; }
            .track-preview { margin: 20px 0; padding: 15px; }
            .album-art { width: 120px; height: 120px; margin: 5px auto 5px auto; }
            .providers { margin: 10px 0; gap: 12px; }
        }
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
        .skeleton { display: none; background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .skeleton.show { display: block; }
        .skeleton-image { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        .skeleton-title { height: 32px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 15px auto; width: 80%; }
        .skeleton-artist { height: 20px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 10px auto; width: 60%; }
        .skeleton-providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .skeleton-provider { height: 56px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .content { opacity: 0; transition: opacity 0.3s ease; }
        .content.loaded { opacity: 1; }
        .track-info { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 30px; }
        .album-art { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; }
        .providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .provider { padding: 18px; color: white; text-decoration: none; border-radius: 8px; transition: all 0.1s ease; font-size: 18px; transform: scale(1); }
        .provider:hover { transform: scale(1.03); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .provider:active { transform: scale(0.97); }
        .provider.spotify { background: #1db954; }
        .provider.spotify:hover { background: #1ed760; }
        .provider.youtube { background: #ff0000; }
        .provider.youtube:hover { background: #cc0000; }
        .provider.apple { background: #000000; }
        .provider.apple:hover { background: #333333; }
        .remember { margin-top: 20px; color: #666; }
    </style>
</head>
<body>
    <h1>snglnk</h1>
    
    <div class="input-container">
        <input type="text" class="url-input" value="<?= htmlspecialchars($originalUrl ?? '') ?>" id="musicUrl" placeholder="Paste a different music link here...">
        <button class="cp-btn" onclick="copyToClipboard()" title="Copy link">Copy</button>
    </div>
    
    <div class="loading" id="loading"></div>
    
    <div class="skeleton" id="skeleton">
        <div class="skeleton-image"></div>
        <div class="skeleton-title"></div>
        <div class="skeleton-artist"></div>
        <p>Choose your music provider:</p>
        <div class="skeleton-providers">
            <div class="skeleton-provider"></div>
            <div class="skeleton-provider"></div>
            <div class="skeleton-provider"></div>
        </div>
    </div>
    
    <div class="content" id="content"></div>
    
    <script>
    // Lazy load the full content via AJAX
    window.addEventListener('load', function() {
        document.getElementById('skeleton').classList.add('show');
        
        fetch('/?api=track-info', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ 
                url: '<?= htmlspecialchars($originalUrl ?? '') ?>'
            })
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                throw new Error(data.error || 'Failed to fetch track info');
            }
            
            // Log performance metrics
            if (data.perf) {
                console.log(`PERF LAZY: Total=${data.perf.total}ms Parse=${data.perf.parse}ms API=${data.perf.api}ms Platform=${data.perf.platform}`);
            }
            
            // Now fetch the HTML content with the track data
            return fetch('/?api=lazy-content', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    trackName: data.track.name,
                    artistName: data.track.artist,
                    albumArt: data.track.albumArt
                })
            });
        })
        .then(response => response.text())
        .then(html => {
            document.getElementById('skeleton').classList.remove('show');
            document.getElementById('content').innerHTML = html;
            document.getElementById('content').classList.add('loaded');
        })
        .catch(error => {
            document.getElementById('skeleton').classList.remove('show');
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
            document.getElementById('skeleton').classList.remove('show');
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
    
    function setPreference(provider) {
        if (document.getElementById("remember").checked) {
            document.cookie = "music_provider=" + provider + "; max-age=" + (365*24*60*60) + "; path=/";
        }
    }
    </script>
</body>
</html>