# Wydajność — pomiary i konfiguracja

Pomiary: Lighthouse 12, headless Chrome, dane demo (12 produktów wariantowych),
lokalne środowisko Docker **bez cache stron**. Data: 2026-07-25.

## Wyniki

| Widok | Desktop | LCP | CLS | Mobile | LCP | CLS | TBT |
|---|---|---|---|---|---|---|---|
| Strona produktu | **97** | 0,7 s | 0,017 | **91** | 2,9 s | 0 | 10 ms |
| Katalog (sklep) | **98** | 0,6 s | 0 | **93** | 2,7 s | 0 | 10 ms |
| Strona główna | **100** | 0,6 s | 0 | **97** | 2,4 s | 0 | 0 ms |

Cele z briefu: mobile ≥ 85 — **spełnione** (91–97). CLS < 0,05 — **spełnione**
(0–0,017). INP < 200 ms — TBT 0–10 ms, czyli z dużym zapasem.

LCP mobile 2,4–2,9 s przy celu < 2,0 s. Pomiar biegnie z symulacją wolnego 4G
i 4× wolniejszym CPU **na instalacji bez cache stron**; dominującą składową jest
czas odpowiedzi serwera (WordPress generuje stronę przy każdym żądaniu). Po
włączeniu cache na hostingu klienta (patrz niżej) ta składowa znika prawie
całkowicie.

## Pomiar na publicznym demo

Po wdrożeniu na Northflank (`https://p01--kramo-wp--bpk4d66g4n48.code.run`), strona
produktu, Lighthouse 12 mobile:

| Metryka | Wynik | Cel |
|---|---|---|
| Wynik wydajności | **97** | ≥ 85 |
| LCP | **1,9 s** | < 2,0 s |
| CLS | **0** | < 0,05 |
| TBT | **0 ms** | INP < 200 ms |
| Odpowiedź serwera | 350 ms | — |

To potwierdza wniosek z pomiarów lokalnych: brakujące 0,9 s LCP wynikało z
czasu odpowiedzi WordPressa bez cache, a nie z kodu motywu. Na realnym
hostingu wszystkie cele z briefu są spełnione.

## Co zostało zrobione

### Zasoby

- **Krytyczny CSS inline** — tokeny, style bazowe i preset (razem ~15 KB) trafiają
  do `<head>` zamiast trzech żądań blokujących render.
- **Style WooCommerce** zostają blokujące na ekranach sklepu (usunięcie ich z
  krytycznej ścieżki przesuwałoby siatkę produktów i psuło CLS), a poza sklepem
  ładują się asynchronicznie z fallbackiem `<noscript>`.
- **Skrypty płatności** (PayPal SDK, Przelewy24) ładują się wyłącznie w koszyku,
  zamówieniu i koncie. Efekt: strona główna 56 → 11 plików JS, katalog 57 → 12,
  strona produktu 67 → 20. Przywrócenie ich gdzie indziej: filtr
  `kramo_load_payment_assets`.
- JS ładowany z `defer`.

### Obrazy

- **WebP** przez wtyczkę Modern Image Formats (`webp-uploads`); oryginały
  pozostają na dysku jako materiał źródłowy.
- Rozmiary dopasowane do siatki i **proporcja 4:5** (katalog 400×500, produkt
  800×1000). Wcześniej WooCommerce serwował kwadratowe 300×300 — zły kadr i za
  mały plik względem slotu.
- `fetchpriority="high"` dokładnie na **jednym** obrazie LCP na widok (pierwszy
  kafelek katalogu / główne zdjęcie produktu). Rozproszenie tej wskazówki na
  kilka obrazów znosi jej działanie.
- `width`/`height` w znaczniku, `loading="lazy"` poza pierwszym ekranem.

### Czcionka

- Subset łaciński + polskie znaki (`ą ć ę ł ń ó ś ź ż` i typografia `„ ” – —`):
  **352 KB → 56,6 KB (−84 %)**, osie zmienne (`wght`, `opsz`) zachowane.
- `font-display: swap`, `preload`, hosting lokalny (bez Google Fonts — RODO).
- Ponowne wygenerowanie: `sh scripts/subset-font.sh` (wymaga `fonttools` i `brotli`).

### CLS

Największe źródło przesunięć na stronie produktu to galeria: przed startem
flexslidera slajdy stoją jeden pod drugim, po starcie zostaje jeden. Rozwiązanie:
rezerwacja proporcji 4:5 na kontenerze galerii oraz ukrycie paska miniatur na
mobile (tam galeria działa gestem). Efekt: 0,129 → 0,017 (desktop), 0,128 → 0
(mobile).

## Cache — warstwa niezależna od hostingu

`inc/cache.php` wykrywa aktywną wtyczkę (LiteSpeed Cache / WP Rocket / W3 Total
Cache) i stosuje jedną politykę:

- **Koszyk, zamówienie i konto nigdy nie trafiają do cache.** Współdzielony cache
  na tych adresach oznaczałby pokazanie jednemu klientowi koszyka innego klienta.
- Strona produktu jest czyszczona z cache po zmianie stanu magazynowego lub ceny.
- Na ekranie WooCommerce → Status wyświetla się podpowiedź, co włączyć dla
  wykrytej wtyczki.

Na hostingu klienta należy zainstalować wtyczkę pasującą do serwera (LiteSpeed →
LiteSpeed Cache, w innym wypadku WP Rocket lub W3TC) i włączyć cache stron.

## Po wdrożeniu na domenę klienta

PageSpeed Insights nie widzi `localhost`, więc oficjalny pomiar Google robi się
po wdrożeniu. Kolejność:

1. Włączyć wtyczkę cache i sprawdzić, że koszyk/zamówienie nie są cache'owane.
2. Uruchomić PageSpeed Insights na stronie produktu (mobile) i zapisać wynik.
3. Po podmianie zdjęć demo na zdjęcia klienta — powtórzyć pomiar (realne zdjęcia
   ważą znacznie więcej niż pliki demo).
