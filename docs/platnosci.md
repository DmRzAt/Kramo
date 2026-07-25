# Płatności — Przelewy24 i PayPal

## Zasada bezpieczeństwa

Tryb płatności jest kontrolowany przez `KRAMO_PAYMENT_MODE`:

- `sandbox` — wyłącznie dane testowe;
- `live` — wyłącznie dane produkcyjne.

Każdy tryb ma osobny komplet zmiennych. Kod nie pobiera kluczy z drugiego trybu i blokuje zapis statycznych danych dostępowych Przelewy24 oraz PayPal do `wp_options`.

Nie wpisuj kluczy API w panelu WooCommerce. Umieść je w lokalnym `docker/.env` albo jako stałe o identycznych nazwach w `wp-config.php`.

## Oficjalne wtyczki

Projekt instaluje:

- oficjalny moduł Przelewy24 dla WooCommerce 9.x–10.x z serwisu Przelewy24;
- oficjalną wtyczkę WooCommerce PayPal Payments z katalogu WordPress.

Instalację dla nowego projektu wykonuje `scripts/new-project.sh`.

Plik `mu-plugins/kramo-payments-bootstrap.php` ładuje environment override przed zwykłymi wtyczkami. Przy wdrożeniu poza Dockerem skopiuj go do `wp-content/mu-plugins/`; bez tego PayPal może zbudować konfigurację przed załadowaniem motywu.

## Konto testowe Przelewy24

Według aktualnej instrukcji Przelewy24 konto sandbox tworzy się z panelu istniejącego konta Przelewy24: `Moje dane → Konto w SANDBOX`. Oficjalne centrum pomocy wymaga wcześniejszej rejestracji w Przelewy24 i akceptacji umowy. Nie można na tej podstawie zagwarantować rejestracji sandbox bez działalności gospodarczej.

Po utworzeniu sandbox pobierz:

1. Merchant ID;
2. CRC key;
3. API key / klucz do raportów.

W panelu sandbox dodaj publiczny adres IP serwera w `Moje dane → API i konfiguracja`.

Uzupełnij `docker/.env`:

```dotenv
KRAMO_PAYMENT_MODE=sandbox
KRAMO_P24_SANDBOX_MERCHANT_ID=
KRAMO_P24_SANDBOX_CRC_KEY=
KRAMO_P24_SANDBOX_REPORTS_KEY=
```

Po zmianie `.env` odtwórz kontenery:

```sh
docker compose --env-file docker/.env -f docker/docker-compose.yml -p kramo up -d --force-recreate wordpress wpcli
```

Moduł sam przekazuje dynamiczny adres powrotu i powiadomienia oparty o:

```text
https://twoja-domena.pl/?wc-api=przelewy24
```

Endpoint musi być publicznie dostępny przez HTTPS. Na samym `localhost` Przelewy24 nie dostarczy powiadomienia; do testu potrzebna jest publiczna domena albo bezpieczny tunel.

## PayPal Sandbox

Utwórz testową aplikację w PayPal Developer:

1. `Apps & Credentials → Sandbox → Create App`;
2. skopiuj Client ID i Secret;
3. zapisz Merchant ID oraz e-mail testowego sprzedawcy;
4. utwórz osobne konto sandbox kupującego.

Uzupełnij:

```dotenv
KRAMO_PAYPAL_SANDBOX_CLIENT_ID=
KRAMO_PAYPAL_SANDBOX_CLIENT_SECRET=
KRAMO_PAYPAL_SANDBOX_MERCHANT_ID=
KRAMO_PAYPAL_SANDBOX_MERCHANT_EMAIL=
```

Oficjalna wtyczka rejestruje webhook pod adresem:

```text
https://twoja-domena.pl/wp-json/paypal/v1/incoming
```

## Test przed uruchomieniem

Po uzupełnieniu credentials uruchom kontrolę konfiguracji:

```sh
docker compose --env-file docker/.env -f docker/docker-compose.yml -p kramo \
  exec -T wpcli wp eval-file ../../../scripts/check-payments.php
```

Checker kończy się błędem, jeżeli aktywny tryb nie ma pełnego kompletu credentials.

Sprawdź osobno Przelewy24 i PayPal:

1. udana płatność zmienia zamówienie na `Przetwarzanie`;
2. przerwana płatność nie oznacza zamówienia jako opłaconego;
3. po zamknięciu przeglądarki webhook nadal aktualizuje zamówienie;
4. pełny i częściowy zwrot można wysłać z poziomu zamówienia WooCommerce;
5. identyfikator transakcji i notatka operatora są zapisane przy zamówieniu.

WooCommerce tworzy zamówienie przed przekierowaniem do zewnętrznej bramki. Dlatego anulowana płatność może pozostawić zamówienie `Oczekujące na płatność` albo `Nieudane`; nie powinna utworzyć zamówienia `Przetwarzanie`. Automatyczne kasowanie takiego zamówienia usuwałoby ślad audytowy i nie jest włączone.

## Zwrot środków

W zamówieniu wybierz `Zwrot`, wpisz kwotę i użyj przycisku zwrotu przez wybranego operatora. Przycisk zwrotu API jest dostępny dopiero dla zamówienia z potwierdzoną transakcją i prawidłowymi credentials.

## Przełączenie na produkcję

1. Zrób kopię bazy i plików.
2. Uzupełnij wyłącznie zmienne `*_LIVE_*`.
3. Ustaw `KRAMO_PAYMENT_MODE=live`.
4. Odtwórz procesy PHP/kontenery, aby wczytały nowe środowisko.
5. Sprawdź, czy panel pokazuje tryb produkcyjny.
6. Zarejestruj produkcyjne webhooki i domenę Apple Pay, jeśli jest używana.
7. Wykonaj małą prawdziwą transakcję i zwrot.
8. Nigdy nie kopiuj wartości `*_SANDBOX_*` do zmiennych `*_LIVE_*`.

## Źródła

- [Przelewy24 — oficjalny moduł WooCommerce](https://www.przelewy24.pl/do-pobrania/woocommerce)
- [Przelewy24 — konfiguracja konta i sandbox](https://developers.przelewy24.pl/index.php)
- [Przelewy24 — jak założyć środowisko testowe](https://www.przelewy24.pl/centrum-pomocy/wsparcie-techniczne-api/jak-zalozyc-srodowisko-testowe)
- [WooCommerce PayPal Payments — konfiguracja sandbox](https://woocommerce.com/document/woocommerce-paypal-payments/account-setup-and-onboarding/)
- [WooCommerce PayPal Payments — zwroty](https://woocommerce.com/document/woocommerce-paypal-payments/managing-orders-and-refunds/)
