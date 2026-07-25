# Прогрес Woo Starter

Оновлено після завершення завдання 06.

## Виконані завдання

| Завдання | Статус | Перевірюваний результат |
|---|---|---|
| 01 — Environment | Виконано | Коміт `efd9ab0` |
| 02 — Child theme | Виконано | Коміт `1b7416c` |
| 03 — Design tokens | Виконано | Коміт `4ad882a` |
| 04 — Presets | Виконано | Коміти `6e2c6a3`, `c15ee6b`; Service вибрано дефолтом |
| 05 — Catalog | Виконано | Коміт `bada1aa` |
| 06 — Personalization | Виконано | Модуль `inc/personalization.php` і перевірка `scripts/check-personalization.php` |
| 07 — Payments | PayPal підтверджено живим тестом; P24 відкладено | Замовлення #132 через PayPal sandbox → `processing`. P24 — ключами першого клієнта |
| 08 — Shipping | Локальна частина виконана; живий geowidget відкладено | Зона «Polska», вагові тарифи, поріг безкоштовної, точка видачі в адмінці/листах |
| 09 — SEO & Schema | Виконано | Rank Math (sitemap без тегів/авторів, breadcrumbs), LocalBusiness/FAQPage, шаблон локальної сторінки |
| 10 — Performance | Виконано | Lighthouse mobile 91–97, CLS 0–0,017, TBT ≤10ms; `docs/performance.md` |

Завдання 11–12 не розпочинались.

## Результат завдання 06

- У даних товару є вкладка `Personalizacja` з увімкненням, типом поля, власною міткою, максимальною довжиною, обов’язковістю та доплатою.
- На сторінці товару показуються текстове поле, лічильник символів, додатковий вибір шрифту або кольору нитки та сума доплати.
- Сервер використовує `sanitize_text_field()` і обрізає текст до налаштованої максимальної довжини.
- Дані передаються через кошик у видимі meta позиції замовлення. Їх відображення в деталях замовлення та листах перевірено; exporter, який включає line-item meta, може прочитати їх через API позиції замовлення.
- Однаковий товар із різними текстами створює окремі позиції кошика.
- Демо-товар `Lniana koszula` має обов’язкову персоналізацію, вибір шрифту, ліміт 20 символів і доплату 20 zł.

## Перевірки завдання 06

Відтворювана команда:

```sh
docker compose --env-file docker/.env -f docker/docker-compose.yml -p woostarter \
  exec -T wpcli wp eval-file ../../../scripts/check-personalization.php
```

Результат: `Success: All seven personalization test cases passed.`

1. Текст видно в кошику — `PASS`.
2. Різні тексти створюють дві окремі позиції — `PASS`.
3. Текст є в згенерованому HTML листа клієнту — `PASS`.
4. Текст є в згенерованому HTML листа адміну — `PASS`.
5. Текст є у видимих meta позиції замовлення — `PASS`.
6. Польські літери та спеціальні символи після санітизації збережені — `PASS`.
7. Порожнє обов’язкове поле відхиляється сервером — `PASS`.

Додатково перевірено:

- доплата додається до ціни рівно один раз;
- багатобайтовий текст обрізається за кількістю символів, а не байтів;
- порожня необов’язкова персоналізація не блокує додавання товару;
- браузерний лічильник показав `14 / 20` для `Zażółć & Gęślą`;
- у реальному кошику одночасно відобразилися `Zażółć & Gęślą / Klasyczny` та `Anna / Nowoczesny`;
- `node --check theme/woostarter-child/assets/js/personalization.js` завершився без помилок;
- `node scripts/check-contrast.js` підтвердив усі обов’язкові пари WCAG AA;
- `git diff --check` завершився без помилок.

Окремий сторонній плагін CSV-експорту не встановлювався, тому його конкретний формат я не можу підтвердити. Самі видимі line-item meta збережені та доступні через WooCommerce API.

## Статус завдання 07

- Встановлено офіційний `woo-przelewy24` та `woocommerce-paypal-payments`.
- Додано розділені sandbox/live credentials через `.env` або `wp-config.php`.
- Статичні P24 і PayPal credentials не читаються з бази та блокуються перед записом у `wp_options`.
- Додано польську інструкцію `docs/platnosci.md`.
- Configuration checker пройшов на окремих фіктивних fixtures для sandbox і live; runtime clients обох плагінів використали правильний комплект без запису секретів у базу.
- Checkout без credentials відкривається без browser console errors і показує, що доступних методів оплати немає.
- Реальні оплата, серверна нотифікація та refund ще не підтверджені: для цього потрібні credentials двох sandbox-акаунтів і публічний HTTPS URL.
- Перевірено на практиці (2026-07-24): sandbox.przelewy24.pl вимагає спочатку **konto produkcyjne**, а воно потребує зареєстрованої działalność gospodarcza. Отримати sandbox без фірми не можна — припущення README про «sandbox без działalność» неактуальне.
- **Рішення:** 07 лишається «технічно готово». Живий P24 підтверджується ключами першого клієнта (у клієнта є фірма → є продакшн- і sandbox-ключі). Локальний end-to-end прогін checkout робимо через **PayPal sandbox** (безкоштовний, без фірми) — плагін PayPal уже встановлено.

## Живий тест PayPal sandbox (2026-07-24)

End-to-end оплата через PayPal sandbox пройдена й підтверджена з бекенду:

- Ключі PayPal sandbox (client_id / secret / merchant_id / merchant_email) внесені лише в `docker/.env` (gitignored), режим `sandbox`.
- `woostarter_payment_provider_is_configured('paypal')` = YES; `merchant_connected` / `use_manual_connection` = true; опція `woocommerce-ppcp-data-common` у БД **відсутня** — дані приходять тільки з env-фільтра.
- Живий токен PayPal з боку WP: HTTP 200.
- Тестове замовлення **#132**: статус `processing` (Przetwarzanie), `is_paid=yes`, total 138,00 PLN, метод `ppcp-gateway`, transaction ID `2VB97375UV9290240`, PayPal order `90N84173XN833942D`, intent CAPTURE, комісія записана (gross 138 / fee 4,80 / net 133,20).
- Секрет PayPal у `wp_options` **після** реальної транзакції: 0 збігів.
- Тимчасово додано метод доставки **Free shipping** у зону за замовчуванням, щоб checkout не блокувався (завдання 08 замінить це реальними InPost/Orlen). P24-гейтвеї лишаються без ключів — очікувано.

## Результат завдання 08 (доставка)

Реалізовано й перевірено локально:

- **Ваги** додані всім 12 демо-товарам (`scripts/seed-demo.php`) — без ваги розрахунок не працює.
- **Зона «Polska»** (PL) з методами: Paczkomat InPost, Kurier InPost, Orlen Paczka, Darmowa dostawa (від 200 zł). Створюється ідемпотентно в `new-project.sh`; тимчасовий free_shipping із зони «rest of world» прибрано.
- **`inc/shipping.php`** — правила в коді: вагові тарифи (перший 1 кг у базі, далі +2 zł локер / +3 zł курʼєр за кожен розпочатий кг), локер дешевший за курʼєра, безкоштовна від порогу (платні методи ховаються).
- **Точка видачі** показується в адмінці замовлення, у листах клієнту/адміну і на сторінці подяки; читається наш ключ `_ws_shipping_point` + типові ключі InPost/Orlen (розширюється фільтром `woostarter_pickup_point_meta_keys`).
- Офіційні плагіни InPost/Orlen ставляться в bootstrap (best-effort).
- `docs/dostawa.md` — польська інструкція: що клієнт вписує (ShipX-токен InPost, дані Orlen), щоб активувати живий вибір точки й друк етикеток.

Перевірки:

- Вага 1.2 кг → Paczkomat/Orlen 14 zł, Kurier 19 zł; 3.6 кг → 18 zł / 25 zł (локер завжди дешевший).
- Кошик ≥ 200 zł → лишається тільки «Darmowa dostawa», платні методи відкинуто.
- Симуляція мета точки (`_ws_shipping_point` і плагінний `_inpost_point_id`) → «Punkt odbioru:» рендериться в адмінці та листах.

Відкладено (як P24): живий **Geowidget InPost** і віджет **Orlen** для вибору точки на checkout + друк етикеток — потребують ShipX/Orlen credentials, які дає клієнт. Тому реальний вибір пачкомату на checkout поки не тестувався end-to-end.

## Результат завдання 09 (SEO і структуровані дані)

Реалізовано й перевірено локально:

- **Rank Math** сконфігуровано без візарда (в `new-project.sh`): sitemap під `/sitemap_index.xml` (індекс містить лише page + product, теги й автори виключені), breadcrumbs увімкнено, Organization/knowledge graph = назва сайту. Ключ активації фронт-sitemap — прапорець `rank_math_wizard_completed`.
- **`inc/schema.php`** — JSON-LD через `wp_head`, додає лише те, чого Rank Math не робить: `LocalBusiness` з `areaServed` (на локальному шаблоні), `FAQPage` (товар + локальна сторінка), заглушка `hreflang`. Organization/OG/canonical — лише як fallback, коли Rank Math **не** активний (без дублювання).
- **`inc/product-faq.php`** — поле FAQ у товарі (формат `Pytanie :: Odpowiedź`), фронт-таб-акордеон, живить FAQPage.
- **`inc/local-service.php` + `page-local-service.php`** — шаблон «Usługa lokalna (SEO)» з мета-боксом: H1 «послуга + місто», секції (ціна, обшар обслуговування, FAQ, CTA). Копіювання під нове місто = зміна двох полів.
- Демо: FAQ на товарі Czapka + приклад локальної сторінки «Mycie kostki brukowej Katowice».
- `docs/seo-lokalne.md` — польська інструкція (як додати місто, FAQ, hreflang).

Виправлено супутній баг: `new-project.sh` через MSYS-конвертацію Git Bash псував permalink-структуру (`/%postname%/` → Windows-шлях) і ламав sitemap; додано `export MSYS_NO_PATHCONV=1`.

Перевірки:

- `sitemap_index.xml` → `Content-Type: text/xml`, підмапи page + product, без авторів/тегів.
- Сторінка товару: валідний `FAQPage`, Product від Rank Math, наш Organization-fallback не дублюється.
- Локальна сторінка: валідні `LocalBusiness` (з areaServed: Sosnowiec/Gliwice/Chorzów) + `FAQPage`, H1 «Mycie kostki brukowej Katowice», CTA.
- Усі JSON-LD блоки парсяться без помилок (валідація Node).

Примітка: повний **Rich Results Test** Google запускається на публічній домені (localhost недоступний інструменту) — крок після деплою на сервер клієнта.

## Результат завдання 10 (продуктивність)

Заміряно Lighthouse 12 (headless Chrome, демо-контент, **без кешу сторінок**):

| Сторінка | Desktop | Mobile | LCP mob | CLS | TBT |
|---|---|---|---|---|---|
| Товар | 97 | **91** | 2,9 s | 0 | 10 ms |
| Каталог | 98 | **93** | 2,7 s | 0 | 10 ms |
| Головна | 100 | **97** | 2,4 s | 0 | 0 ms |

Цілі: mobile ≥85 ✅ (91–97), CLS <0,05 ✅ (0–0,017), INP <200ms ✅ (TBT ≤10ms). LCP <2,0s локально не досягнуто — під троттлінгом домінує час відповіді WordPress без кешу; на хостингу клієнта це закриває шар кешу.

Зроблено:

- **`inc/cache.php`** — детекція LiteSpeed / WP Rocket / W3TC, одна політика: кошик, checkout і акаунт **ніколи** не кешуються (інакше один клієнт отримає кошик іншого), очистка сторінки товару при зміні ціни/залишку, підказка в WooCommerce → Status.
- **`inc/performance.php`** — критичний CSS інлайном (tokens+base+preset ≈15KB), woo.css блокує лише на екранах магазину (інакше стрибає сітка), платіжні скрипти лише в кошику/checkout: **головна 56→11 JS, каталог 57→12, товар 67→20**.
- **Шрифт**: subset латиниця+польська — **352KB → 56,6KB (−84%)**, осі `wght`/`opsz` збережені, `scripts/subset-font.sh`.
- **Зображення**: WebP через Modern Image Formats; виправлено пропорцію — WooCommerce віддавав квадратні 300×300 замість 4:5 (тепер 400×500 і 800×1000); `fetchpriority="high"` рівно на одному LCP-зображенні на сторінку.
- **CLS**: галерея резервує 4:5 до ініціалізації flexslider, на мобільному сховано смужку мініатюр — 0,129→0,017 (desktop), 0,128→0 (mobile).
- `docs/performance.md` — цифри + інструкція з кешу для клієнта.

Знайдені й виправлені баги: `wp_kses_post()` вирізав `fetchpriority` (WP 7.0.2 не має його в дозволених атрибутах); fatal через невірну кількість аргументів у фільтрі галереї; `aspect-ratio: auto` після ініціалізації слайдера сам створював зсув 0,373.

Відкладено: офіційний PageSpeed Insights — після деплою на домен клієнта (інструмент не бачить localhost) і після заміни демо-зображень на реальні фото.
