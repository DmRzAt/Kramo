# Bezpieczeństwo i kopie zapasowe

## Co jest już włączone

Bez ciężkich wtyczek typu Wordfence — zabezpieczenia siedzą w motywie
(`inc/security.php`) i w konfiguracji, więc nie obciążają serwera.

| Zabezpieczenie | Stan |
|---|---|
| Nagłówki `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy`, `Cross-Origin-Opener-Policy` | włączone |
| `Strict-Transport-Security` | włącza się automatycznie na HTTPS |
| Ukrycie wersji WordPressa (meta generator, `?ver=` w plikach) | włączone |
| Wyłączenie XML-RPC (odpowiada 403) | włączone |
| Blokada edytora plików w panelu (`DISALLOW_FILE_EDIT`) | włączone |
| Ograniczenie prób logowania | wtyczka Limit Login Attempts Reloaded |
| Ogólny komunikat błędu logowania (bez informacji, czy login istnieje) | włączone |
| Blokada wyliczania użytkowników (`?author=1`, `/wp-json/wp/v2/users`) | włączone |
| Antyspam formularzy: honeypot + pomiar czasu wypełnienia | włączone |

Antyspam celowo nie używa reCAPTCHA — to skrypty Google ładowane na każdej
stronie i dodatkowy wątek RODO.

Aby włączyć XML-RPC (np. gdy klient korzysta z aplikacji mobilnej WordPress):

```php
add_filter( 'woostarter_enable_xmlrpc', '__return_true' );
```

## RODO — zgoda na cookie

Baner pojawia się tylko wtedy, gdy w **Wygląd → Woo Starter** wpisano
identyfikator GA4 lub Meta Pixel.

Kluczowa zasada: **skrypty analityczne nie są w ogóle wysyłane do przeglądarki
przed zgodą.** Nie są ładowane i blokowane — po prostu nie istnieją na stronie
do momentu kliknięcia „Akceptuję". Zweryfikowano w przeglądarce: przed zgodą
zero żądań do `googletagmanager.com`, po zgodzie skrypt ładuje się z poprawnym
identyfikatorem.

Wybór zapisuje cookie `ws_consent` na 180 dni (`SameSite=Lax`, na HTTPS
dodatkowo `Secure`).

Szablony dokumentów: `docs/polityka-prywatnosci.md`, `docs/regulamin.md`.
Oba wymagają uzupełnienia danych firmy i sprawdzenia przez prawnika.

## Kopie zapasowe (UpdraftPlus)

Harmonogram ustawiony w bootstrapie:

- **Baza danych — codziennie**, przechowywane 30 kopii.
- **Pliki — co tydzień**, przechowywane 14 kopii.

### Co musi zrobić klient

Miejsce przechowywania celowo nie jest ustawione — kopie muszą trafiać na
**konto klienta**, nie na serwer, na którym stoi sklep:

1. **Ustawienia → UpdraftPlus** → zakładka **Ustawienia**.
2. W „Wybierz miejsce docelowe" wskazać Google Drive lub Dropbox.
3. Kliknąć link autoryzacji i zalogować się na konto klienta.
4. Zapisać zmiany i kliknąć **Utwórz kopię zapasową** — pierwsza kopia powinna
   pojawić się na dysku klienta.

### Test odtworzenia (wykonany)

Odtworzenie zostało przetestowane na środowisku lokalnym:

1. Wykonano pełną kopię (baza + pliki) — archiwa: baza 91,6 KB, wtyczki 38,2 MB,
   motywy 14,2 MB, uploads 426 KB.
2. Usunięto produkt „Czapka bawełniana" wraz z 4 wariantami (symulacja utraty
   danych) — liczba produktów spadła z 12 do 11.
3. Odtworzono bazę z archiwum UpdraftPlus.
4. Wynik: 12 produktów, „Czapka bawełniana" z 4 wariantami z powrotem, sklep,
   katalog, strona produktu i koszyk odpowiadają kodem 200, ustawienia
   (preset, strefa wysyłki, format obrazów, harmonogram kopii) nienaruszone.

Ten sam test należy powtórzyć **raz na środowisku klienta** po podłączeniu
Google Drive lub Dropboksa — kopia, której nikt nie odtworzył, nie jest jeszcze
kopią zapasową.

### Odtworzenie z panelu

**Ustawienia → UpdraftPlus → Istniejące kopie zapasowe** → przy wybranej kopii
**Przywróć** → zaznaczyć komponenty (baza, wtyczki, motywy, uploads) → potwierdzić.
