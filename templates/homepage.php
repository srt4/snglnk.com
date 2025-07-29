<!DOCTYPE html>
<html>
<head>
    <title>snglnk - Universal Music Link Converter</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    <!-- Open Graph / WhatsApp Preview -->
    <meta property="og:type" content="website">
    <meta property="og:title" content="snglnk - Universal Music Link Converter">
    <meta property="og:description" content="Paste any music link and choose your preferred music app">
    <meta property="og:image" content="https://snglnk.com/og-image.png">
    <meta property="og:url" content="https://snglnk.com">
    <meta property="og:site_name" content="snglnk">
    
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; text-align: center; font-size: 18px; }
        @media (max-width: 768px) {
            body { margin: 0 auto; padding: 15px; }
            h1 { margin-top: 0; }
            .track-preview { margin: 20px 0; padding: 15px; }
            .album-art { width: 120px; height: 120px; margin: 5px auto 5px auto; }
            .providers { margin: 10px 0; gap: 12px; }
        }
        .input-container { margin: 30px 0; display: -webkit-flex; display: -moz-flex; display: flex; -webkit-align-items: center; -moz-align-items: center; align-items: center; gap: 10px; width: 100%; max-width: 500px; margin-left: auto; margin-right: auto; }
        .input-container > * + * { margin-left: 10px; }
        .url-input { -webkit-flex: 1; -moz-flex: 1; flex: 1; padding: 18px; font-size: 18px; border: 2px solid #ddd; border-radius: 8px; box-sizing: border-box; }
        .url-input:focus { outline: none; border-color: #007acc; }
        .cp-btn { padding: 18px 20px; background: #28a745; color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 18px; display: none; transition: all 0.1s ease; -webkit-flex-shrink: 0; -moz-flex-shrink: 0; flex-shrink: 0; min-width: 90px; transform: scale(1); }
        .cp-btn:hover { background: #1e7e34; transform: scale(1.02); }
        .cp-btn:active { transform: scale(0.98); }
        .track-preview { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; display: none; opacity: 0; transition: opacity 0.2s ease; }
        .track-preview.show { opacity: 1; }
        .loading { display: none; color: #666; font-size: 24px; }
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
        .skeleton { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; display: none; opacity: 0; transition: opacity 0.2s ease; }
        .skeleton.show { display: block; opacity: 1; }
        .skeleton-image { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; }
        .skeleton-title { height: 32px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 15px auto; width: 80%; }
        .skeleton-artist { height: 20px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 4px; margin: 10px auto; width: 60%; }
        .skeleton-providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .skeleton-provider { height: 56px; background: linear-gradient(90deg, #e0e0e0 25%, #f0f0f0 50%, #e0e0e0 75%); background-size: 200% 100%; animation: shimmer 1.5s infinite; border-radius: 8px; }
        @keyframes shimmer {
            0% { background-position: -200% 0; }
            100% { background-position: 200% 0; }
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
    <p>Paste any music link and choose your preferred music app</p>
    
    <div class="input-container">
        <input type="text" class="url-input" placeholder="Paste Spotify, YouTube Music, or Apple Music link here..." id="musicUrl">
        <button class="cp-btn" id="shareBtn" onclick="copyToClipboard()" title="Copy link">Copy</button>
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
    
    <div class="track-preview" id="trackPreview">
        <div id="albumArt"></div>
        <h2 id="trackName"></h2>
        <p id="artistName"></p>
        
        <p>Choose your music provider:</p>
        <div class="providers" id="providers"></div>
        
        <div class="remember">
            <label><input type="checkbox" id="remember" checked> Remember my choice</label>
        </div>
    </div>
    
    <script>
    let debounceTimer;
    
    document.getElementById('musicUrl').addEventListener('input', function() {
        const url = this.value.trim();
        
        clearTimeout(debounceTimer);
        
        // Instant feedback when editing starts
        const preview = document.getElementById('trackPreview');
        preview.classList.remove('show');
        setTimeout(() => preview.style.display = 'none', 200);
        document.getElementById('loading').style.display = 'none';
        document.getElementById('skeleton').classList.remove('show');
        document.getElementById('shareBtn').style.display = 'none';
        
        if (url === '') {
            // Just clear the URL, don't redirect
            history.pushState({}, '', '/');
            return;
        }
        
        // Super snappy response!
        debounceTimer = setTimeout(() => {
            fetchTrackInfo(url);
        }, 150);
    });
    
    function fetchTrackInfo(url) {
        document.getElementById('skeleton').classList.add('show');
        document.getElementById('trackPreview').style.display = 'none';
        
        // AJAX call to get track info
        fetch('/?api=track-info', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ url: url })
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('skeleton').classList.remove('show');
            
            if (data.success) {
                // Show share button
                document.getElementById('shareBtn').style.display = 'block';
                
                // Update browser URL
                const cleanUrl = url.replace(/^https?:\/\//, '');
                history.pushState({}, '', '/' + cleanUrl);
                
                // Log performance metrics
                if (data.perf) {
                    console.log(`PERF: Total=${data.perf.total}ms Parse=${data.perf.parse}ms API=${data.perf.api}ms Platform=${data.perf.platform}`);
                }
                
                // Check if user has preference and redirect immediately
                if (data.hasPreference && data.redirectUrl) {
                    window.location.href = data.redirectUrl;
                    return;
                }
                
                // Show track preview with smooth animation
                showTrackPreview(data.track);
            } else {
                // Hide share button on error
                document.getElementById('shareBtn').style.display = 'none';
            }
        })
        .catch(error => {
            document.getElementById('skeleton').classList.remove('show');
            document.getElementById('shareBtn').style.display = 'none';
            console.error('Error:', error);
        });
    }
    
    function showTrackPreview(track) {
        document.getElementById('trackName').textContent = track.name;
        document.getElementById('artistName').textContent = 'by ' + track.artist;
        
        const albumArtContainer = document.getElementById('albumArt');
        if (track.albumArt) {
            albumArtContainer.innerHTML = '<img src="' + track.albumArt + '" alt="Album Art" class="album-art">';
        } else {
            albumArtContainer.innerHTML = '';
        }
        
        const providersContainer = document.getElementById('providers');
        providersContainer.innerHTML = '';
        
        track.providers.forEach(provider => {
            const link = document.createElement('a');
            link.href = provider.url;
            link.className = 'provider ' + provider.name;
            link.textContent = provider.displayName;
            link.onclick = () => setPreference(provider.name);
            providersContainer.appendChild(link);
        });
        
        const preview = document.getElementById('trackPreview');
        preview.style.display = 'block';
        // Trigger animation after display
        setTimeout(() => preview.classList.add('show'), 10);
    }
    
    function setPreference(provider) {
        if (document.getElementById('remember').checked) {
            document.cookie = 'music_provider=' + provider + '; max-age=' + (365*24*60*60) + '; path=/';
        }
    }
    
    function copyToClipboard() {
        const currentUrl = window.location.href;
        navigator.clipboard.writeText(currentUrl).then(() => {
            // Show temporary feedback
            const shareBtn = document.getElementById('shareBtn');
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
            const shareBtn = document.getElementById('shareBtn');
            shareBtn.innerHTML = 'Copied!';
            setTimeout(() => {
                shareBtn.innerHTML = 'Copy';
            }, 1000);
        });
    }
    </script>
</body>
</html>