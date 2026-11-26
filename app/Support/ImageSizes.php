<?php

namespace App\Support;

/**
 * Zalecane wymiary grafik w CMS-ie — jedno źródło dla pulpitu panelu (karta „Wymiary grafik”) i biblioteki Multimedia.
 * Te same wartości podają podpowiedzi przy polach formularzy; zmieniając wymiar tutaj, popraw też podpowiedź przy polu.
 */
class ImageSizes
{
    /** @var array<int, array{0: string, 1: string, 2: string}> [gdzie, wymiar, uwaga] */
    public const LIST = [
        ['Logo', '400×400 px', 'PNG albo SVG z przezroczystym tłem'],
        ['Obraz do udostępniania (OG)', '1200×630 px', 'podgląd linku na Facebooku, LinkedInie, w Messengerze'],
        ['Slajder na stronie głównej', '1600×600 px', 'ważne elementy trzymaj w środku kadru'],
        ['Panel misji w sliderze', 'min. 1280×500 px', 'napisy są białe — wybierz ciemniejsze zdjęcie'],
        ['Baner nad treścią strony', '1600×400 px', 'szeroki, wąski kadr'],
        ['Zdjęcie w tle „Wesprzyj nas”', 'min. 1600×500 px', ''],
        ['Miniatura aktualności i kart', '800×600 px', 'proporcje 4:3'],
        ['Zdjęcie działania (projektu)', '1200×500 px', ''],
        ['Zdjęcie w galerii', 'min. 600×450 px', ''],
        ['Obraz w mega menu', '640×360 px', 'proporcje 16:9'],
        ['Okładka podcastu', '1400×1400 px', 'kwadrat, do 4 MB'],
        ['Logo partnera', 'do 300 px szerokości', 'PNG z przezroczystym tłem'],
    ];

    /**
     * Banery według stref (Banery → Strefy): [strefa, wymiar, uwaga]. Strefy pełnej szerokości mają kontener 1152 px
     * (max-w-6xl), kolumna boczna aktualności 256 px — wymiary podwojone dla ostrości na ekranach o dużej gęstości.
     *
     * @var array<int, array{0: string, 1: string, 2: string}>
     */
    public const BANNERS = [
        ['Nagłówek (pod menu)', '1200×120 px', 'proporcje 10:1; dla ostrości 2400×240 px'],
        ['Pod okruszkami (podstrony)', '1200×120 px', 'proporcje 10:1; dla ostrości 2400×240 px'],
        ['Strona główna (pod skrótami)', '1200×120 px', 'proporcje 10:1; dla ostrości 2400×240 px'],
        ['Stopka', '1200×120 px', 'proporcje 10:1; dla ostrości 2400×240 px'],
        ['Kolumna boczna aktualności', '512×640 px', 'wyświetlany w szerokości 256 px, pionowy 4:5'],
    ];

    /** Uwaga pod tabelą banerów. */
    public const BANNERS_NOTE = 'Na baner duży, kontrastowy tekst; cały tekst z grafiki przepisz do tekstu alternatywnego. Baner się nie przycina — zmniejsza się do szerokości ekranu.';

    /** Formaty przyjmowane przez panel (wspólna uwaga pod listą). */
    public const FORMATS_NOTE = 'Przyjmujemy JPG, PNG, WebP, AVIF i SVG (logo). Zdjęcia HEIC z iPhone’a są same zamieniane na WebP. Zdjęcia są przycinane, więc inne proporcje też zadziałają — podane wymiary dają najostrzejszy wygląd.';
}
