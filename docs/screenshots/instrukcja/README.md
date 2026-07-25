# Zrzuty ekranu do instrukcji klienta

Instrukcja (`docs/instrukcja-klienta.md`) czyta się bez obrazków, więc PDF
buduje się nawet gdy tego katalogu nie ma. Zrzuty dodaje się per projekt —
robione na **sklepie klienta**, z jego produktami, są dużo bardziej czytelne
niż z danych demo.

## Lista zrzutów

| Plik | Ekran | Rozdział |
|---|---|---|
| `01-logowanie.png` | ekran logowania `/wp-admin` | 1 |
| `02-dodaj-produkt.png` | Produkty → Dodaj nowy, widok całej strony | 2 |
| `03-dane-produktu.png` | ramka „Dane produktu", zakładka Ogólne (cena) | 2 |
| `04-wysylka-waga.png` | zakładka Wysyłka z polem wagi | 2 |
| `05-atrybuty.png` | zakładka Atrybuty z Kolor/Rozmiar | 3 |
| `06-warianty.png` | zakładka Warianty po wygenerowaniu | 3 |
| `07-personalizacja.png` | zakładka Personalizacja | 4 |
| `08-zamowienia.png` | WooCommerce → Zamówienia (lista) | 6 |
| `09-zamowienie-szczegoly.png` | pojedyncze zamówienie z personalizacją i punktem odbioru | 6 |
| `10-kupon.png` | Marketing → Kupony → Dodaj kupon | 7 |
| `11-updraftplus.png` | Ustawienia → UpdraftPlus → Istniejące kopie zapasowe | 8 |

## Jak wstawić do instrukcji

W odpowiednim miejscu `instrukcja-klienta.md`:

```markdown
![Dodawanie produktu](screenshots/instrukcja/02-dodaj-produkt.png)
```

Ścieżki są względne wobec katalogu `docs/`, więc `scripts/make-manual.sh`
znajdzie je bez dodatkowej konfiguracji.

## Wskazówki

- Szerokość okna przeglądarki około 1400 px — panel wygląda wtedy tak, jak u
  klienta na laptopie.
- Zrzut samego fragmentu ekranu (ramka „Dane produktu"), a nie całego monitora.
- Zamaskuj dane osobowe na zrzutach zamówień.
