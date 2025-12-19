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
        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --card-bg: #f5f5f5;
            --border-color: #ddd;
            --input-bg: #ffffff;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --card-bg: #2d2d2d;
            --border-color: #444;
            --input-bg: #333333;
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

        .track-info {
            background: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        @media (max-width: 1024px) {
            body {
                margin: 0 auto;
                padding: 15px;
            }

            h1 {
                margin-top: 0;
            }

            h2 {
                margin: 0;
            }

            p {
                margin: 0.3em 0;
            }

            .track-info {
                margin-bottom: 5px !important;
            }
        }

        .providers {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .provider {
            padding: 15px;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            transition: background 0.2s;
        }

        .provider.spotify {
            background: #1db954;
        }

        .provider.spotify:hover {
            background: #1ed760;
        }

        .provider.youtube {
            background: #ff0000;
        }

        .provider.youtube:hover {
            background: #cc0000;
        }

        .provider.apple {
            background: #000000;
        }

        .provider.apple:hover {
            background: #333333;
        }

        .remember {
            margin-top: 20px;
            color: #666;
        }

        .album-art {
            width: 150px;
            height: 150px;
            margin: 10px auto;
            border-radius: 8px;
        }

        .input-container {
            margin: 20px 0;
            display: -webkit-flex;
            display: -moz-flex;
            display: flex;
            -webkit-align-items: center;
            -moz-align-items: center;
            align-items: center;
            gap: 10px;
            width: 100%;
            max-width: 500px;
            margin-left: auto;
            margin-right: auto;
        }

        .input-container>*+* {
            margin-left: 10px;
        }

        .url-input {
            -webkit-flex: 1;
            -moz-flex: 1;
            flex: 1;
            padding: 16px;
            font-size: 16px;
            border: 2px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            background: white;
        }

        .url-input:focus {
            outline: none;
            border-color: #007acc;
        }

        .cp-btn {
            padding: 16px 20px;
            background: #28a745;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.2s;
            display: block !important;
            -webkit-flex-shrink: 0;
            -moz-flex-shrink: 0;
            flex-shrink: 0;
            min-width: 80px;
        }

        .cp-btn:hover {
            background: #1e7e34;
        }

        padding: 16px 14px;
        background: #6c5ce7;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 14px;
        transition: all 0.1s ease;
        -webkit-flex-shrink: 0;
        flex-shrink: 0;
        font-weight: bold;
        }

        background: #5b4cdb;
        transform: scale(1.02);
        }

        transform: scale(0.98);
        }

        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.8);
        z-index: 1000;
        justify-content: center;
        align-items: center;
        }

        display: flex;
        }

        background: var(--card-bg);
        padding: 30px;
        border-radius: 16px;
        text-align: center;
        }

        margin: 0 auto 15px;
        }

        border-radius: 8px;
        }

        color: #888;
        margin: 10px 0;
        font-size: 14px;
        }

        padding: 12px 30px;
        background: #6c5ce7;
        color: white;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        font-size: 16px;
        }

        background: #5b4cdb;
        }
    </style>
    <style>
        .theme-toggle {
            position: fixed;
            top: 15px;
            right: 15px;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 50%;
            width: 44px;
            height: 44px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: all 0.2s ease;
            z-index: 100;
        }

        .theme-toggle:hover {
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <button class="theme-toggle" id="themeToggle" onclick="toggleTheme()" title="Toggle dark/light mode">🌙</button>

    <div class="input-container">
        <input type="text" class="url-input" value="<?= htmlspecialchars($originalUrl ?? '') ?>" id="musicUrl"
            placeholder="Paste a different music link here...">
        <button class="cp-btn" onclick="copyToClipboard()" title="Copy link">Copy</button>
    </div>

    <!-- QR Code Modal -->
    </div>
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
            <a href="<?= $provider['url'] ?>" class="provider <?= $provider['name'] ?>"
                onclick="setPreference('<?= $provider['name'] ?>')">
                <?= ucfirst($provider['name']) ?>
            </a>
        <?php endforeach; ?>
    </div>
    <div class="remember">
        <label><input type="checkbox" id="remember" checked> Remember my choice</label>
    </div>

    <script>
        // Dark mode functionality with localStorage persistence
        function initTheme() {
            const savedTheme = localStorage.getItem('snglnk-theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                setTheme(systemDark ? 'dark' : 'light');
            }

            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('snglnk-theme')) {
                    setTheme(e.matches ? 'dark' : 'light');
                }
            });
        }

        function setTheme(theme) {
            document.documentElement.setAttribute('data-theme', theme);
            const toggle = document.getElementById('themeToggle');
            if (toggle) toggle.textContent = theme === 'dark' ? '☀️' : '🌙';
        }

        function toggleTheme() {
            const current = document.documentElement.getAttribute('data-theme');
            const newTheme = current === 'dark' ? 'light' : 'dark';
            localStorage.setItem('snglnk-theme', newTheme);
            setTheme(newTheme);
        }

        // Initialize theme on page load
        initTheme();

        function setPreference(provider) {
            if (document.getElementById("remember").checked) {
                document.cookie = "music_provider=" + provider + "; max-age=" + (365 * 24 * 60 * 60) + "; path=/";
            }
        }

        let debounceTimer;
        let originalUrl = document.getElementById('musicUrl').value;

        document.getElementById('musicUrl').addEventListener('input', function () {
            const url = this.value.trim();

            clearTimeout(debounceTimer);

            // Hide track info when editing starts
            if (url !== originalUrl) {
                document.querySelector('.track-info').style.display = 'none';
            }

            if (url === '') {
                // Clear URL but stay on page
                history.pushState({}, '', '/');
                return;
            }

            // Lightning fast redirect!
            debounceTimer = setTimeout(() => {
                const cleanUrl = url.replace(/^https?:\/\//, '');
                window.location.href = '/' + cleanUrl;
            }, 200);
        });

        function copyToClipboard() {
            const currentUrl = window.location.href;
            navigator.clipboard.writeText(currentUrl).then(() => {
                // Show temporary feedback
                const shareBtn = document.querySelector('.cp-btn');
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