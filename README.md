# snglnk

Universal music link converter. Takes a music URL from any platform and redirects users to their preferred music service.

User preferences are stored in cookies. Generates social media previews with track and artist information.

## Platform Compatibility

| Platform | Status | Track Info | Artist Info | Notes |
|----------|--------|------------|-------------|-------|
| Spotify | Fully | ✓ | ✓ | Full API integration |
| YouTube Music | Fully | ✓ | ✓ | oEmbed API, cleans "- Topic" suffixes |
| Apple Music | Fully | ✓ | ✓ | iTunes Search API |
| Deezer | Fully | ✓ | ✓ | Public API |
| Tidal | Broken | ✗ | ✗ | Shows "Tidal Track #ID", requires auth |
| SoundCloud | Partially | ✓ | ✓ | Basic URL parsing, no API |

## Setup

1. Copy the configuration template:
   ```bash
   cp config.example.php config.php
   ```

2. Add your Spotify API credentials to `config.php`

3. Deploy to web server

## Usage

Convert any music link by prepending your domain:

```
snglnk.com/https://open.spotify.com/track/4iV5W9uYEdYUVa79Axb7Rh
snglnk.com/https://music.youtube.com/watch?v=dQw4w9WgXcQ
snglnk.com/https://music.apple.com/us/song/never-gonna-give-you-up/1560019815
```

First-time visitors see a provider selection page. Return visitors are redirected to their saved preference.

To reset preferences: `snglnk.com/?reset`

## File Structure

```
index.php                     Main application entry point
config.php                    Configuration file (gitignored)
config.example.php            Configuration template
providers/
├── MusicProvider.php         Abstract base class
├── ProviderManager.php       Manages all providers
├── SpotifyProvider.php       Spotify API integration
├── YouTubeProvider.php       YouTube oEmbed integration
├── AppleMusicProvider.php    iTunes Search API integration
├── DeezerProvider.php        Deezer public API integration
├── TidalProvider.php         Tidal page scraping
└── SoundCloudProvider.php    Basic URL parsing
```