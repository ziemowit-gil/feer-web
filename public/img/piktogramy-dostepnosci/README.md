# Piktogramy dostępności

22 jednokolorowe piktogramy SVG (48×48) oznaczające udogodnienia dostępnościowe.

- **Autor piktogramów:** Tomasz Grabowski
- **Prawa autorskie:** Fundacja Kultury Bez Barier (https://kulturabezbarier.org/)
- **Licencja:** Creative Commons Uznanie autorstwa – Na tych samych warunkach 4.0 (CC BY-SA 4.0),
  pełny tekst w `LICENSE.txt` i na https://creativecommons.org/licenses/by-sa/4.0/legalcode.pl
- **Źródło:** https://github.com/JacZad/piktogramy-dostepnosci (commit 01bdec1, 20.10.2026)

Licencja wymaga podania autorstwa i linku do licencji przy każdej publikacji. weCMS robi to sam:
pod stopką każdego szablonu stoi podpis „Piktogramy: Tomasz Grabowski, prawa autorskie: Fundacja Kultury
Bez Barier, licencja CC BY-SA 4.0” z linkami (`resources/views/partials/pictograms-credit.blade.php`,
`App\Support\AccessibilityPictograms::attribution()`).

## Zmiany względem oryginału

- Pliki SVG skopiowano bez zmian.
- Na stronie w ikonach modułów dostępności (przycisk „Dostępność”, wideo PJM — klasy `.picto-icon .picto-{klucz}`
  w `resources/css/app.css`) piktogramy są **przekolorowane**: kształt z pliku służy jako maska CSS, a kolor
  pochodzi z koloru tekstu przycisku (np. biały na ciemnym pasku). Blok „Piktogramy dostępności” pokazuje
  oryginalny kolor na białym polu.

## Utwory zależne

Każda zmieniona wersja piktogramów (np. inny kolor, przycięcie, połączenie z innym znakiem) jest utworem
zależnym i **musi być udostępniana na tej samej licencji CC BY-SA 4.0**, z podaniem autora (Tomasz Grabowski),
właściciela praw (Fundacja Kultury Bez Barier), linku do licencji i informacji, co zmieniono. Zmieniając
pliki tutaj, dopisz zmianę w sekcji wyżej.

Lista piktogramów i ich nazwy (teksty alternatywne): `App\Support\AccessibilityPictograms::ALL`.
