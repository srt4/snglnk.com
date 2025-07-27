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
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 100px auto; padding: 20px; text-align: center; }
        .input-container { margin: 30px 0; }
        .url-input { width: 100%; max-width: 500px; padding: 15px; font-size: 16px; border: 2px solid #ddd; border-radius: 8px; }
        .url-input:focus { outline: none; border-color: #007acc; }
        .track-preview { margin: 30px 0; padding: 20px; background: #f5f5f5; border-radius: 8px; display: none; }
        .album-art { width: 150px; height: 150px; margin: 10px auto; border-radius: 8px; }
        .providers { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0; }
        .provider { padding: 15px; color: white; text-decoration: none; border-radius: 8px; transition: background 0.2s; }
        .provider.spotify { background: #1db954; }
        .provider.spotify:hover { background: #1ed760; }
        .provider.youtube { background: #ff0000; }
        .provider.youtube:hover { background: #cc0000; }
        .provider.apple { background: #000000; }
        .provider.apple:hover { background: #333333; }
        .remember { margin-top: 20px; color: #666; }
        .loading { display: none; color: #666; }
    </style>
</head>
<body>
    <h1>snglnk</h1>
    <p>Paste any music link and choose your preferred music app</p>
    
    <div class="input-container">
        <input type="text" class="url-input" placeholder="Paste Spotify, YouTube Music, or Apple Music link here..." id="musicUrl">
    </div>
    
    <div class="loading" id="loading">Finding track info...</div>
    
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
        
        if (url === '') {
            document.getElementById('trackPreview').style.display = 'none';
            document.getElementById('loading').style.display = 'none';
            return;
        }
        
        // Debounce the AJAX call
        debounceTimer = setTimeout(() => {
            fetchTrackInfo(url);
        }, 500);
    });
    
    function fetchTrackInfo(url) {
        document.getElementById('loading').style.display = 'block';
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
            document.getElementById('loading').style.display = 'none';
            
            if (data.success) {
                // Check if user has preference and redirect immediately
                if (data.hasPreference && data.redirectUrl) {
                    window.location.href = data.redirectUrl;
                    return;
                }
                
                // Show track preview
                showTrackPreview(data.track);
            }
        })
        .catch(error => {
            document.getElementById('loading').style.display = 'none';
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
        
        document.getElementById('trackPreview').style.display = 'block';
    }
    
    function setPreference(provider) {
        if (document.getElementById('remember').checked) {
            document.cookie = 'music_provider=' + provider + '; max-age=' + (365*24*60*60) + '; path=/';
        }
    }
    </script>
</body>
</html>