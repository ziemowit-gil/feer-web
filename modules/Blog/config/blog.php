<?php

return [
    // Prefiks publicznych adresów bloga: /wiem, /wiem/{wpis}, /wiem/autor/{autor}, /wiem/rss.xml.
    'prefix' => env('BLOG_PREFIX', 'wiem'),

    // Dawne adresy, przekierowywane na stałe (301) pod prefiks powyżej.
    'legacy_prefixes' => ['wiem-feer', 'blog'],

    // Slug witryny sieci, w której oprawie (nazwa, logo, kolory, menu) renderuje się blog.
    // Brak takiej witryny = blog w oprawie witryny głównej. Zakłada ją `php artisan blog:witryna`.
    'site_slug' => env('BLOG_SITE_SLUG', 'wiem'),
];
