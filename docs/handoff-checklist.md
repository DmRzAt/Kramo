# Checklist przekazania sklepu

Projekt: **{{NAZWA_PROJEKTU}}** · Domena: **{{DOMENA}}** · Data: **{{DATA}}**

Nic nie jest przekazane, dopóki każdy punkt nie jest odhaczony **na domenie
produkcyjnej**, nie na środowisku testowym.

## Płatności

- [ ] Klucze produkcyjne Przelewy24 wpisane w `wp-config.php` lub `.env`
      (nigdy w bazie, nigdy w repozytorium)
- [ ] `KRAMO_PAYMENT_MODE` ustawiony na `live`
- [ ] Klucze PayPal produkcyjne wpisane
- [ ] **Testowe zamówienie na bojowych kluczach** przeszło do statusu
      „Przetwarzanie"
- [ ] Powiadomienie o płatności dociera, gdy klient zamknie przeglądarkę
- [ ] Zwrot z panelu przetestowany na tym zamówieniu
- [ ] Zamówienie testowe usunięte lub oznaczone

## Dostawa

- [ ] Token InPost ShipX (produkcyjny) wpisany, Geowidget działa na zamówieniu
- [ ] Orlen Paczka skonfigurowana (jeśli w zakresie)
- [ ] Wybrany paczkomat widoczny w panelu zamówienia i w e-mailu
- [ ] Progi darmowej dostawy i ceny zgodne z ustaleniami
- [ ] Wszystkie produkty mają wpisaną wagę

## Treści i prawo

- [ ] Regulamin uzupełniony danymi firmy i **sprawdzony przez klienta**
- [ ] Polityka prywatności uzupełniona i sprawdzona
- [ ] Strona regulaminu podpięta w WooCommerce (zgoda w zamówieniu)
- [ ] Dane firmy (NIP, adres, kontakt) poprawne w stopce i na stronie kontaktu
- [ ] Ceny, koszty wysyłki i czasy realizacji zgodne z rzeczywistością

## Analityka i RODO

- [ ] GA4 podpięty na domenie produkcyjnej i zbiera dane
- [ ] Meta Pixel podpięty (jeśli w zakresie)
- [ ] Baner zgody pokazuje się nowemu użytkownikowi
- [ ] **Przed zgodą brak żądań do googletagmanager.com** (sprawdzone w
      narzędziach deweloperskich, zakładka Sieć)
- [ ] Po kliknięciu „Akceptuję" statystyki zaczynają działać

## Bezpieczeństwo i kopie

- [ ] SSL aktywny, przekierowanie z `http` na `https` działa
- [ ] `DISALLOW_FILE_EDIT` włączony
- [ ] Limit prób logowania aktywny
- [ ] XML-RPC zwraca 403 (albo świadomie włączony, jeśli klient używa aplikacji)
- [ ] UpdraftPlus podpięty do **konta klienta** (Google Drive / Dropbox)
- [ ] Pierwsza kopia wykonana i widoczna na tym koncie
- [ ] **Odtworzenie przetestowane na produkcji przynajmniej raz**

## SEO

- [ ] Sklep widoczny dla wyszukiwarek (Ustawienia → Czytanie: odznaczone
      „Proś wyszukiwarki o nieindeksowanie")
- [ ] Sitemap `/sitemap_index.xml` otwiera się i zawiera produkty
- [ ] Sitemap zgłoszona w Google Search Console
- [ ] Rich Results Test bez błędów na stronie produktu
- [ ] Rich Results Test bez błędów na stronie usługi lokalnej (jeśli w zakresie)

## Wydajność

- [ ] Wtyczka cache dobrana do hostingu i włączona
- [ ] Koszyk, Zamówienie i Moje konto **wykluczone z cache** (sprawdzone na
      dwóch przeglądarkach: koszyk jednego klienta nie pojawia się u drugiego)
- [ ] PageSpeed Insights (mobile) na stronie produktu zmierzony i zapisany
- [ ] Zdjęcia klienta wgrane w docelowych rozmiarach (nie prosto z aparatu)

## Dostępy

- [ ] Klient ma własne konto administratora z własnym hasłem
- [ ] Hasła przekazane bezpiecznym kanałem (nie e-mailem, nie w czacie)
- [ ] **Tymczasowe konta wykonawcy i testowe konta usunięte**
- [ ] Dostęp do hostingu, domeny i skrzynki pocztowej po stronie klienta
- [ ] Dostęp do Search Console i Analytics na koncie klienta

## Dokumentacja

- [ ] `instrukcja-klienta.md` uzupełniona danymi projektu
- [ ] PDF wygenerowany (`sh scripts/make-manual.sh`) i przekazany
- [ ] Klient przeszedł krótkie szkolenie: dodanie produktu, obsługa zamówienia,
      wystawienie kuponu
- [ ] Ustalone zasady wsparcia po wdrożeniu (zakres, czas reakcji, koszt)

---

Podpis wykonawcy: ****************** Data: **********

Podpis klienta: ****************** Data: **********
