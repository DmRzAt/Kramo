# Dostawa — konfiguracja i przekazanie klientowi

Ten dokument opisuje, jak działa wysyłka w szablonie i co musi zrobić klient,
aby uruchomić wybór paczkomatu / punktu odbioru na żywo.

## Co jest już skonfigurowane

Strefa wysyłki **Polska** (kraj: PL) z metodami:

| Metoda | Typ | Koszt bazowy |
|---|---|---|
| Paczkomat InPost | punkt odbioru | 12 zł |
| Kurier InPost | kurier | 16 zł |
| Orlen Paczka | punkt odbioru | 12 zł |
| Darmowa dostawa | od 200 zł | 0 zł |

Zasady liczone w kodzie (`theme/woostarter-child/inc/shipping.php`):

- **Waga.** Pierwszy 1 kg w cenie bazowej; każdy rozpoczęty kolejny kilogram
  dolicza 2 zł (punkt odbioru) lub 3 zł (kurier).
- **Punkt odbioru tańszy niż kurier** — wynika z profili cenowych.
- **Darmowa dostawa od 200 zł** — powyżej progu klient widzi tylko darmową
  wysyłkę, płatne metody są ukrywane.

Próg i stawki zmienia się w jednym miejscu — filtr `woostarter_shipping_config`
lub bezpośrednio w `inc/shipping.php`.

> **Waga produktów jest wymagana.** Bez wagi dopłaty się nie policzą. Dane demo
> (`scripts/seed-demo.php`) mają wagi ustawione; realne produkty klienta również
> muszą mieć wagę w zakładce „Wysyłka”.

## Co musi dostarczyć klient (żeby ruszył wybór punktu na żywo)

Wybór paczkomatu w koszyku (Geowidget) i druk etykiet zapewniają oficjalne
wtyczki. Wymagają one danych dostępowych, które klient zakłada na swoje konto:

### InPost (Paczkomaty + Kurier)

1. Konto w **InPost ShipX** (https://manager.paczkomaty.pl) — organizacja z
   podpisaną umową.
2. Token API ShipX (sandbox do testów, produkcyjny na żywo) oraz ID organizacji.
3. Wtyczka **InPost dla WooCommerce** → ustawienia → wpisać token i ID.
4. Włączyć **Geowidget** dla metody Paczkomat, aby na checkout pojawiła się mapa
   wyboru punktu.

### Orlen Paczka

1. Konto partnera **Orlen Paczka** i dane API.
2. Wtyczka **Orlen Paczka** → ustawienia → wpisać dane, włączyć widget wyboru
   punktu.

Wtyczki instaluje skrypt `scripts/new-project.sh` (best-effort). Jeśli danej
wtyczki nie ma w repozytorium wp.org, instaluje się ją ręcznie paczką od
dostawcy.

## Jak punkt odbioru trafia do zamówienia

Niezależnie od wtyczki, wybrany punkt jest pokazywany:

- w panelu zamówienia (pod adresem wysyłki),
- w e-mailach do klienta i do sklepu,
- na stronie „podziękowania za zamówienie”.

Szablon czyta klucz `_ws_shipping_point` oraz typowe klucze wtyczek InPost/Orlen.
Jeśli konkretna wersja wtyczki zapisuje punkt pod innym kluczem, wystarczy dodać
go filtrem:

```php
add_filter( 'woostarter_pickup_point_meta_keys', function ( $keys ) {
    $keys[] = '_moj_klucz_punktu';
    return $keys;
} );
```

## Status

- Reguły wagowe, próg darmowej wysyłki i „punkt tańszy niż kurier” — działają
  lokalnie (przetestowane).
- Wybór punktu na żywo (Geowidget InPost / widget Orlen) i druk etykiet — po
  wpisaniu danych ShipX / Orlen przez klienta. To ten sam mechanizm co przy
  płatnościach Przelewy24: dane produkcyjne dostarcza klient.
