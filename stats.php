<?php
require_once 'ShortLinkCache.php';

$shortLinks = new ShortLinkCache();
$stats = $shortLinks->getStats();
$topLinks = $shortLinks->getTopLinks(20);
?>
<!DOCTYPE html>
<html>

<head>
    <title>snglnk Stats</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            --bg-color: #ffffff;
            --text-color: #000000;
            --card-bg: #f5f5f5;
            --border-color: #ddd;
        }

        [data-theme="dark"] {
            --bg-color: #1a1a1a;
            --text-color: #e0e0e0;
            --card-bg: #2d2d2d;
            --border-color: #444;
        }

        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background-color: var(--bg-color);
            color: var(--text-color);
        }

        h1 {
            margin-bottom: 10px;
        }

        .subtitle {
            color: #888;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            padding: 25px;
            border-radius: 12px;
            text-align: center;
        }

        .stat-value {
            font-size: 48px;
            font-weight: bold;
            color: #6c5ce7;
        }

        .stat-label {
            color: #888;
            margin-top: 5px;
        }

        .top-links {
            background: var(--card-bg);
            border-radius: 12px;
            overflow: hidden;
        }

        .top-links h2 {
            padding: 20px;
            margin: 0;
            border-bottom: 1px solid var(--border-color);
        }

        .link-row {
            display: flex;
            align-items: center;
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .link-row:last-child {
            border-bottom: none;
        }

        .link-rank {
            width: 30px;
            color: #888;
            font-weight: bold;
        }

        .link-info {
            flex: 1;
            min-width: 0;
        }

        .link-name {
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .link-code {
            color: #888;
            font-size: 14px;
        }

        .link-clicks {
            font-weight: bold;
            color: #6c5ce7;
            padding: 6px 12px;
            background: rgba(108, 92, 231, 0.1);
            border-radius: 20px;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #6c5ce7;
            text-decoration: none;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .empty-state {
            padding: 40px;
            text-align: center;
            color: #888;
        }

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

    <a href="/" class="back-link">← Back to snglnk</a>
    <h1>📊 snglnk Stats</h1>
    <p class="subtitle">Link analytics and click tracking</p>

    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_links'] ?? 0 ?></div>
            <div class="stat-label">Total Links</div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?= $stats['total_clicks'] ?? 0 ?></div>
            <div class="stat-label">Total Clicks</div>
        </div>
    </div>

    <div class="top-links">
        <h2>🔥 Top Clicked Links</h2>
        <?php if (empty($topLinks)): ?>
            <div class="empty-state">
                <p>No links tracked yet. Share some music!</p>
            </div>
        <?php else: ?>
            <?php foreach ($topLinks as $i => $link): ?>
                <div class="link-row">
                    <div class="link-rank"><?= $i + 1 ?></div>
                    <div class="link-info">
                        <div class="link-name"><?= htmlspecialchars($link['track_name'] ?: $link['original_url']) ?></div>
                        <div class="link-code">snglnk.com/<?= htmlspecialchars($link['short_code']) ?> • Last:
                            <?= $link['last_clicked'] ?></div>
                    </div>
                    <div class="link-clicks"><?= $link['clicks'] ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function initTheme() {
            const savedTheme = localStorage.getItem('snglnk-theme');
            if (savedTheme) {
                setTheme(savedTheme);
            } else {
                const systemDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                setTheme(systemDark ? 'dark' : 'light');
            }
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

        initTheme();
    </script>
</body>

</html>