# SEO i strony lokalne

## Rank Math (skonfigurowane w bootstrapie)

- Sitemap włączony pod `/sitemap_index.xml`, z wyłączonymi tagami i autorami.
- Okruszki (breadcrumbs) włączone.
- Wiedza o firmie (Organization / knowledge graph) ustawiona na nazwę sklepu.
- Schemat produktu generuje Rank Math automatycznie.

Motyw dokłada tylko to, czego Rank Math nie robi sam: `LocalBusiness` z
`areaServed`, `FAQPage` oraz zaślepkę `hreflang`.

## Strona usługi lokalnej (kolejne miasta)

Szablon **„Usługa lokalna (SEO)"** obsługuje zamówienia typu
„Mycie kostki brukowej Katowice". Przykładowa strona jest w danych demo.

### Jak dodać nowe miasto (2 pola)

1. **Strony → Wszystkie strony** → najedź na stronę usługi → **Duplikuj**
   (lub utwórz nową stronę i w „Atrybuty strony → Szablon" wybierz
   „Usługa lokalna (SEO)").
2. W panelu **„Usługa lokalna (SEO)"** pod treścią zmień:
   - **Usługa** — np. „Mycie kostki brukowej"
   - **Miasto** — np. „Kraków"
3. Uzupełnij (opcjonalnie): Obszar obsługi (miasta po przecinku), Cena,
   Telefon, FAQ, przycisk CTA.
4. Zapisz. H1, treść i dane `LocalBusiness` (z `areaServed`) ustawią się same.

To wszystko — jedna strona = jedno miasto, a kopiowanie to podmiana dwóch pól.

## FAQ produktu

W produkcie zakładka **FAQ** → pola w formacie `Pytanie :: Odpowiedź`
(jedna para na linię). Wypełnione pytania:

- pokazują się jako zakładka „FAQ" na stronie produktu,
- trafiają do danych `FAQPage` (szansa na rozszerzony wynik w Google).

## hreflang (na przyszłość)

Domyślnie wyłączony. Gdy dojdą języki, mapę alternatyw ustawia filtr:

```php
add_filter( 'kramo_hreflang_alternates', function ( $map ) {
    $map['en'] = 'https://example.com/en/...';
    return $map;
} );
```

## Weryfikacja

Struktura JSON-LD jest poprawna (zweryfikowana lokalnie). Pełny
**Rich Results Test** Google uruchamia się na publicznej domenie
(narzędzie Google nie widzi `localhost`) — to krok po wdrożeniu na serwer
klienta.
