{
    "name": "{{ $settings->site_name }}",
    "short_name": "FEER",
    "start_url": "/",
    "display": "standalone",
    "background_color": "#ffffff",
    "theme_color": "{{ $settings->brandPalette()['color'] }}",
    "description": "{{ Str::limit($settings->meta_description, 100) }}",
    "icons": [
        { "src": "/img/pwa-icon-192.png", "sizes": "192x192", "type": "image/png" },
        { "src": "/img/pwa-icon-512.png", "sizes": "512x512", "type": "image/png" }
    ]
}
