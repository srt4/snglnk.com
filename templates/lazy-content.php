<div class="track-info">
    <?php if ($albumArt): ?>
        <img src="<?= htmlspecialchars($albumArt) ?>" alt="Album Art" style="width: 150px; height: 150px; margin: 10px auto; border-radius: 8px;">
    <?php endif; ?>
    <h2><?= htmlspecialchars($trackName) ?></h2>
    <p>by <?= htmlspecialchars($artistName) ?></p>
</div>
<p>Choose your music provider:</p>
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 20px 0;">
    <?php foreach ($providers as $provider): ?>
        <?php
        $style = '';
        switch($provider['name']) {
            case 'spotify': $style = 'background: #1db954;'; break;
            case 'youtube': $style = 'background: #ff0000;'; break;
            case 'apple': $style = 'background: #000000;'; break;
        }
        ?>
        <a href="<?= $provider['url'] ?>" 
           style="padding: 18px; color: white; text-decoration: none; border-radius: 8px; font-size: 18px; transition: all 0.1s ease; <?= $style ?>" 
           onmouseover="this.style.transform='scale(1.03)'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.15)';" 
           onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';" 
           onclick="setPreference('<?= $provider['name'] ?>')">
            <?= $provider['displayName'] ?>
        </a>
    <?php endforeach; ?>
</div>
<div style="margin-top: 20px; color: #666;">
    <label><input type="checkbox" id="remember" checked> Remember my choice</label>
</div>
<script>
function setPreference(provider) {
    if (document.getElementById("remember").checked) {
        document.cookie = "music_provider=" + provider + "; max-age=" + (365*24*60*60) + "; path=/";
    }
}
</script>