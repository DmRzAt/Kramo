---
title: "Instrukcja obsługi sklepu"
subtitle: "{{NAZWA_SKLEPU}}"
author: "{{NAZWA_WYKONAWCY}}"
date: "{{DATA}}"
lang: pl
---

# Zanim zaczniesz

Ta instrukcja jest napisana dla osoby, która nie zajmuje się programowaniem.
Wszystko, co tu opisano, robi się myszką w panelu sklepu.

Adres panelu: **{{ADRES_SKLEPU}}/wp-admin**

Jeśli coś pójdzie nie tak, nic nie jest stracone — kopie zapasowe robią się
automatycznie (rozdział 8).

\newpage

# 1. Logowanie do panelu

1. Wejdź na **{{ADRES_SKLEPU}}/wp-admin**.
2. Wpisz login **{{LOGIN}}** i hasło.
3. Kliknij **Zaloguj się**.

Po zalogowaniu widzisz pulpit. Po lewej stronie jest menu — z niego korzystasz
przez cały czas.

**Zapamiętaj:**

- Po pięciu błędnych próbach logowania system zablokuje możliwość kolejnych na
  kilka minut. To ochrona przed włamaniem, nie błąd.
- Nie podawaj nikomu swojego hasła. Jeśli ktoś ma pomóc przy sklepie, załóż mu
  osobne konto: **Użytkownicy → Dodaj nowego**.
- Hasło zmienisz w **Użytkownicy → Profil**.

\newpage

# 2. Dodawanie produktu

**Produkty → Dodaj nowy**

1. **Nazwa** — wpisz na górze, np. „Lniana koszula".
2. **Opis** — duże pole pod nazwą. Tu piszesz wszystko o produkcie.
3. **Krótki opis produktu** — pole niżej. To jedno–dwa zdania, które klient
   widzi obok zdjęcia.
4. **Dane produktu** — ramka na środku:
   - **Cena zwykła** — cena w złotych, np. `159`.
   - **Cena promocyjna** — wypełnij tylko, gdy robisz promocję. Wtedy na
     produkcie pojawi się plakietka „Promocja".
   - Zakładka **Magazyn** — jeśli chcesz liczyć sztuki, zaznacz „Zarządzaj
     stanem magazynowym" i wpisz liczbę.
   - Zakładka **Wysyłka** — **wpisz wagę w kilogramach**. Bez wagi koszt
     wysyłki policzy się źle.
5. **Zdjęcie produktu** — prawa kolumna, „Ustaw zdjęcie produktu".
6. **Galeria produktu** — pozostałe zdjęcia (drugie zdjęcie pokazuje się, gdy
   klient najedzie myszką na produkt w katalogu).
7. **Kategorie produktu** — prawa kolumna, zaznacz jedną lub kilka.
8. Kliknij **Opublikuj**.

> **Wskazówka:** zanim opublikujesz, kliknij **Podgląd** — zobaczysz produkt
> oczami klienta.

\newpage

# 3. Warianty produktu (kolor, rozmiar)

Warianty stosuje się, gdy ten sam produkt występuje w kilku kolorach lub
rozmiarach i ma jedną stronę zamiast pięciu.

1. W ramce **Dane produktu** zmień listę z „Produkt prosty" na
   **„Produkt z wariantami"**.
2. Zakładka **Atrybuty**:
   - Wybierz z listy **Kolor**, zaznacz potrzebne kolory.
   - Zaznacz **„Używany do wariantów"**.
   - Kliknij **Dodaj**, powtórz dla **Rozmiar**.
   - **Zapisz atrybuty**.
3. Zakładka **Warianty**:
   - Kliknij **Wygeneruj warianty** — system utworzy wszystkie kombinacje.
   - Rozwiń każdy wariant i wpisz **cenę** (możesz też dodać osobne zdjęcie i
     stan magazynowy).
   - **Zapisz zmiany**.
4. **Aktualizuj** produkt.

**Gdy jakiejś kombinacji nie ma w sprzedaży** — ustaw jej stan magazynowy na
„Brak w magazynie". Klient zobaczy ją jako niedostępną, ale nie zniknie mu z
oczu (to celowe: łatwiej wybrać inny rozmiar niż szukać, czy w ogóle istnieje).

\newpage

# 4. Personalizacja (imię, napis, haft)

Pozwala klientowi wpisać własny tekst przy zamówieniu.

**Jak włączyć dla produktu:**

1. Otwórz produkt do edycji.
2. W ramce **Dane produktu** wejdź w zakładkę **Personalizacja**.
3. Zaznacz **Włącz personalizację**.
4. Ustaw:
   - **Typ pola** — sam tekst, tekst z wyborem kroju pisma albo tekst z
     kolorem nici.
   - **Etykieta pola** — co zobaczy klient, np. „Imię do haftu".
   - **Maksymalna długość** — ile znaków, np. `20`.
   - **Pole obowiązkowe** — czy można kupić bez wpisania tekstu.
   - **Dopłata** — opcjonalna kwota doliczana do ceny.
5. **Aktualizuj**.

**Gdzie zobaczysz wpisany tekst:** w koszyku klienta, w e-mailu z zamówieniem,
w e-mailu do Ciebie oraz w szczegółach zamówienia w panelu. Nie musisz o niego
pytać telefonicznie.

> Dwa takie same produkty z różnym napisem to w koszyku dwie osobne pozycje —
> to normalne i celowe.

\newpage

# 5. Zdjęcia — jak przygotować

| Co | Jak |
|---|---|
| Kształt | pionowe, proporcja 4:5 (np. 1200 × 1500 pikseli) |
| Format | JPG (sklep sam zrobi lżejszą wersję WebP) |
| Waga pliku | najlepiej poniżej 500 KB |
| Tło | jednolite, najlepiej to samo we wszystkich produktach |

**Dlaczego to ważne:** zdjęcia w różnych proporcjach psują wygląd katalogu —
kafelki przestają być równe. Zdjęcia prosto z telefonu (5–8 MB) spowalniają
sklep, a wolny sklep gorzej sprzedaje i niżej wyświetla się w Google.

Prosty sposób na zmniejszenie: [squoosh.app](https://squoosh.app) — wrzuć
zdjęcie, ustaw szerokość 1200 i pobierz.

\newpage

# 6. Zamówienia

**WooCommerce → Zamówienia**

Lista pokazuje numer, klienta, kwotę i status.

**Statusy:**

| Status | Co oznacza |
|---|---|
| Wstrzymane | czeka na płatność |
| Przetwarzanie | opłacone, do wysłania |
| Zrealizowane | wysłane, sprawa zamknięta |
| Anulowane | zamówienie odwołane |
| Zwrócone | pieniądze oddane |

**Codzienna praca:**

1. Kliknij numer zamówienia — zobaczysz produkty, adres, wybrany paczkomat i
   tekst personalizacji.
2. Spakuj i wyślij.
3. Zmień status na **Zrealizowane** — klient dostanie automatycznie e-mail.

**Zwrot pieniędzy:** w zamówieniu kliknij **Zwrot**, wpisz kwotę i potwierdź.
Przy płatnościach elektronicznych pieniądze wracają do klienta automatycznie.

\newpage

# 7. Kody rabatowe

**Marketing → Kupony → Dodaj kupon**

1. **Kod** — to, co wpisze klient, np. `WIOSNA10`.
2. **Typ rabatu**:
   - „Procentowy rabat na koszyk" — np. 10% od całości.
   - „Rabat kwotowy na koszyk" — np. 50 zł.
3. **Wartość rabatu** — sama liczba, bez `%` i bez `zł`.
4. Zakładka **Ograniczenia użycia**:
   - **Minimalna kwota zamówienia** — żeby rabat działał od określonej sumy.
   - **Limit użycia na kupon** — ile razy w sumie można go użyć.
5. Zakładka **Ogólne** → **Data ważności kuponu**.
6. **Opublikuj**.

Kod klient wpisuje w koszyku w polu „Masz kupon?".

\newpage

# 8. Kopie zapasowe

Kopie robią się same:

- **baza danych** (zamówienia, produkty, teksty) — **codziennie**,
- **pliki** (zdjęcia, motyw, wtyczki) — **co tydzień**.

**Gdzie leżą:** {{MIEJSCE_KOPII}} (konto Google Drive / Dropbox sklepu).

**Jak sprawdzić, że działają:** **Ustawienia → UpdraftPlus** → zakładka
**Istniejące kopie zapasowe**. Powinny być wpisy z ostatnich dni.

**Jak przywrócić:**

1. **Ustawienia → UpdraftPlus → Istniejące kopie zapasowe**.
2. Wybierz kopię z dnia, w którym wszystko działało.
3. Kliknij **Przywróć**.
4. Zaznacz, co przywrócić (zwykle „Baza danych" wystarczy, gdy zniknęły
   produkty lub treści).
5. Potwierdź i poczekaj — nie zamykaj okna.

> Odtwarzanie zostało przetestowane przy wdrożeniu, więc kopie na pewno
> działają.

\newpage

# 9. Co robić, gdy coś nie działa

**Zanim zadzwonisz — sprawdź po kolei:**

1. **Odśwież stronę** klawiszem `Ctrl + F5` (Windows) lub `Cmd + Shift + R`
   (Mac). To rozwiązuje większość „dziwnych" problemów z wyglądem.
2. **Sprawdź w innej przeglądarce** albo w oknie prywatnym — jeśli tam działa,
   problem jest po stronie Twojej przeglądarki, nie sklepu.
3. **Sprawdź na telefonie** — czy problem występuje wszędzie.

**Typowe sytuacje:**

| Objaw | Co zrobić |
|---|---|
| Produkt nie widać w sklepie | sprawdź, czy jest **Opublikowany**, a nie „Szkic"; czy ma kategorię |
| Zły koszt wysyłki | sprawdź, czy produkt ma wpisaną **wagę** |
| Klient nie dostał e-maila | poproś, by sprawdził folder spam; sprawdź adres w zamówieniu |
| Nie da się kupić wariantu | prawdopodobnie ma stan magazynowy 0 |
| Zniknęły treści po zmianach | przywróć kopię zapasową (rozdział 8) |

**Czego nie robić:**

- Nie usuwaj ani nie aktualizuj wtyczek „na wszelki wypadek".
- Nie zmieniaj ustawień w **Ustawienia → Bezpośrednie odnośniki**.
- Nie usuwaj stron: Koszyk, Zamówienie, Moje konto — sklep bez nich nie działa.

**Kontakt:** {{KONTAKT_WYKONAWCY}}

Przy zgłoszeniu napisz: co robiłeś, co się stało, i dołącz zrzut ekranu.
To skraca naprawę o połowę.
