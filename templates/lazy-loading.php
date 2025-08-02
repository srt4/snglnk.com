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
        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --card-bg: #f5f5f5;
            --border-color: #ddd;
            --input-bg: #ffffff;
            --shimmer-start: #e0e0e0;
            --shimmer-mid: #f0f0f0;
            --shimmer-end: #e0e0e0;
        }
        
        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --card-bg: #2d2d2d;
            --border-color: #444;
            --input-bg: #333333;
            --shimmer-start: #404040;
            --shimmer-mid: #505050;
            --shimmer-end: #404040;
        }
        
        body { 
            font-family: Arial, sans-serif; 
            max-width: 600px; 
            margin: 50px auto; 
            padding: 20px; 
            text-align: center; 
            font-size: 18px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }
        @media (max-width: 1024px) {
            body { margin: 0 auto; padding: 15px; }
            h1 { margin-top: 0; }
            h2 { margin: 0; }
            p { margin: 0.3em 0; }
            .track-preview { margin: 20px 0; padding: 15px; }
            .album-art { width: 120px; height: 120px; margin: 5px auto 5px auto; }
            .providers { margin: 10px 0; gap: 12px; }
            .track-info { margin-bottom: 5px !important; }
        }
        .input-container { margin: 20px 0; display: -webkit-flex; display: -moz-flex; display: flex; -webkit-align-items: center; -moz-align-items: center; align-items: center; gap: 10px; width: 100%; max-width: 500px; margin-left: auto; margin-right: auto; }
        .input-container > * + * { margin-left: 10px; }
        .url-input { 
            -webkit-flex: 1; -moz-flex: 1; flex: 1; 
            padding: 16px; font-size: 16px; 
            border: 2px solid var(--border-color); 
            border-radius: 8px; box-sizing: border-box; 
            background: var(--input-bg); 
            color: var(--text-color);
        }
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
        .skeleton { 
            display: none; 
            background: var(--card-bg); 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 30px;
        }
        .skeleton.show { display: block; }
        .skeleton-image { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; background: linear-gradient(90deg, var(--shimmer-start) 25%, var(--shimmer-mid) 50%, var(--shimmer-end) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        .skeleton-title { height: 32px; background: linear-gradient(90deg, var(--shimmer-start) 25%, var(--shimmer-mid) 50%, var(--shimmer-end) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 15px auto; width: 80%; }
        .skeleton-artist { height: 20px; background: linear-gradient(90deg, var(--shimmer-start) 25%, var(--shimmer-mid) 50%, var(--shimmer-end) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 10px auto; width: 60%; }
        .skeleton-providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .skeleton-provider { height: 56px; background: linear-gradient(90deg, var(--shimmer-start) 25%, var(--shimmer-mid) 50%, var(--shimmer-end) 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
        }
        .content { opacity: 0; transition: opacity 0.3s ease; }
        .content.loaded { opacity: 1; }
        .track-info { 
            background: var(--card-bg); 
            padding: 20px; 
            border-radius: 8px; 
            margin-bottom: 30px;
        }
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
    // Dark mode functionality - system detection only
    function initTheme() {
        const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const theme = systemDark ? 'dark' : 'light';
        setTheme(theme);
        
        // Listen for system theme changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            setTheme(e.matches ? 'dark' : 'light');
        });
    }
    
    function setTheme(theme) {
        document.documentElement.setAttribute('data-theme', theme);
    }
    
    // Initialize theme on page load
    initTheme();

    // Lazy load the full content via AJAX
    window.addEventListener('load', function() {
        document.getElementById('skeleton').classList.add('show');
        
        let trackData = null; // Store track data for short link creation
        
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
            
            // Store track data for later use
            trackData = data.track;
            
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
            
            // Create short link after content is loaded
            if (trackData) {
                const originalUrl = '<?= htmlspecialchars($originalUrl ?? '') ?>';
                const cleanUrl = originalUrl.replace(/^https?:\/\//, '');
                createShortLink(cleanUrl, trackData.name, trackData.artist, trackData.albumArt);
            }
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
            // Don't update URL immediately - let the content load first, then update URL
            loadLazyContent(url);
        }, 200);
    });
    
    function loadLazyContent(url) {
        document.getElementById('skeleton').classList.add('show');
        document.getElementById('content').style.display = 'none';
        
        let trackData = null; // Store track data for short link creation
        
        // Fetch the track info and lazy content
        fetch('/?api=track-info', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ url: url })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Store track data for later use
                trackData = data.track;
                
                // Check if user has preference and should redirect
                if (data.hasPreference && data.redirectUrl) {
                    // Update URL before redirect
                    const cleanUrl = url.replace(/^https?:\/\//, '');
                    history.pushState({}, '', '/' + cleanUrl);
                    window.location.href = data.redirectUrl;
                    return;
                }
                
                // Load the lazy content
                return fetch('/?api=lazy-content', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        trackName: data.track.name,
                        artistName: data.track.artist,
                        albumArt: data.track.albumArt
                    })
                });
            } else {
                throw new Error('Failed to get track info');
            }
        })
        .then(response => response.text())
        .then(html => {
            // Content loaded successfully - NOW update URL
            const cleanUrl = url.replace(/^https?:\/\//, '');
            history.pushState({}, '', '/' + cleanUrl);
            
            document.getElementById('skeleton').classList.remove('show');
            document.getElementById('content').innerHTML = html;
            document.getElementById('content').classList.add('loaded');
            document.getElementById('content').style.display = 'block';
            
            // Create short link after content is loaded
            if (trackData) {
                createShortLink(cleanUrl, trackData.name, trackData.artist, trackData.albumArt);
            }
        })
        .catch(error => {
            document.getElementById('skeleton').classList.remove('show');
            document.getElementById('content').innerHTML = '<p>Error loading content. Please refresh the page.</p>';
            document.getElementById('content').style.display = 'block';
            console.error('Error:', error);
        });
    }
    
    function copyToClipboard() {
        const urlToCopy = shortUrl || window.location.href;
        navigator.clipboard.writeText(urlToCopy).then(() => {
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
            textArea.value = urlToCopy;
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
    
    let shortUrl = null;
    
    function createShortLink(originalUrl, trackName, artistName, albumArt) {
        fetch('/?api=create-short-link', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                url: originalUrl,
                trackName: trackName,
                artistName: artistName,
                albumArt: albumArt
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                shortUrl = data.short_url;
                const shareBtn = document.querySelector('.cp-btn');
                if (shareBtn) shareBtn.style.display = 'block';
                
                // Update input field to show the short link
                document.getElementById('musicUrl').value = data.short_url;
            } else {
                // Fallback to current URL if short link creation fails
                shortUrl = window.location.href;
                const shareBtn = document.querySelector('.cp-btn');
                if (shareBtn) shareBtn.style.display = 'block';
            }
        })
        .catch(error => {
            // Fallback to current URL on error
            shortUrl = window.location.href;
            const shareBtn = document.querySelector('.cp-btn');
            if (shareBtn) shareBtn.style.display = 'block';
            console.error('Short link creation error:', error);
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