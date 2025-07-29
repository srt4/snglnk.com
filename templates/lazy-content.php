<div class="track-info">
    <?php if ($albumArt): ?>
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
            <?= $provider['displayName'] ?>
        </a>
    <?php endforeach; ?>
</div>
<div class="remember">
    <label><input type="checkbox" id="remember" checked> Remember my choice</label>
</div>